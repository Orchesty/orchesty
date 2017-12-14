import {Channel, Message, Options} from "amqplib";
import {Connection} from "amqplib-plus/dist/lib/Connection";
import {Publisher} from "amqplib-plus/dist/lib/Publisher";
import {IMetrics} from "metrics-sender/dist/lib/metrics/IMetrics";
import logger from "../../logger/Logger";
import {default as CounterMessage} from "../../message/CounterMessage";
import Headers from "../../message/Headers";
import {INodeLabel} from "../Configurator";
import Terminator from "../terminator/Terminator";
import CounterConsumer from "./CounterConsumer";
import {default as CounterProcess, ICounterProcessInfo} from "./CounterProcess";
import ICounter from "./ICounter";
import ICounterStorage from "./storage/ICounterStorage";

export interface ICounterSettings {
    sub: {
        queue: {
            name: string,
            prefetch: number,
            options: any,
        },
    };
    pub: {
        routing_key: string,
        exchange: {
            name: string,
            type: string,
            options: {},
        },
        queue: {
            name: string,
            options: {},
        },
    };
}

/**
 * Topology component that receives signals(messages) and watches if some process run through whole topology
 * If yes, it sends process finished message
 */
export default class Counter implements ICounter {

    private settings: any;
    private connection: Connection;
    private publisher: Publisher;
    private consumer: CounterConsumer;
    private storage: ICounterStorage;
    private terminator: Terminator;
    private metrics: IMetrics;

    /**
     *
     * @param settings
     * @param connection
     * @param storage
     * @param terminator
     * @param metrics
     */
    constructor(
        settings: ICounterSettings,
        connection: Connection,
        storage: ICounterStorage,
        terminator: Terminator,
        metrics: IMetrics,
    ) {
        this.settings = settings;
        this.connection = connection;
        this.storage = storage;
        this.terminator = terminator;
        this.metrics = metrics;

        this.prepareConsumer();
        this.preparePublisher();
    }

    /**
     * Listen to the event stream and keep info about job partial results
     * On job end, send process end message.
     */
    public start(): Promise<void> {
        return this.consumer.consume(this.settings.sub.queue.name, this.settings.sub.queue.options)
            .then(() => {
                logger.info(`Counter started consuming messages from "${this.settings.sub.queue.name}" queue`);

                this.terminator.startServer();
            });
    }

    /**
     * Creates subscription channel
     */
    private prepareConsumer() {
        const prepareFn: any = (ch: Channel) => {
            const s = this.settings;

            return ch.assertQueue(s.sub.queue.name, s.sub.queue.options)
                .then(() => {
                    return ch.prefetch(s.sub.queue.prefetch);
                });
        };

        this.consumer = new CounterConsumer(this.connection, prepareFn, (msg: Message) => {
            this.handleMessage(msg);
        });
    }

    /**
     * Creates publish channel
     */
    private preparePublisher() {
        const prepareFn: any = (ch: Channel) => {
            const pubExSett = this.settings.pub.exchange;
            const pubQSett = this.settings.pub.queue;

            return Promise.all([
                ch.assertExchange(pubExSett.name, pubExSett.type, pubExSett.options),
                ch.assertQueue(pubQSett.name, pubQSett.options),
            ]).then(() => {
                return ch.bindQueue(pubQSett.name, pubExSett.name, this.settings.pub.routing_key);
            });
        };

        this.publisher = new Publisher(this.connection, prepareFn);
    }

    /**
     * Handles incoming message
     *
     * @param {Message} msg
     * @return {boolean}
     */
    private handleMessage(msg: Message): void {
        try {
            const headers = new Headers(msg.properties.headers);
            const content = JSON.parse(msg.content.toString());

            const resultCode = content.result.code;
            const processId = CounterProcess.getMostTopProcessId(headers.getPFHeader(Headers.PROCESS_ID));
            headers.setPFHeader(Headers.PROCESS_ID, processId);

            const node: INodeLabel = {
                id: headers.getPFHeader(Headers.NODE_ID),
                node_id: headers.getPFHeader(Headers.NODE_ID),
                node_name: headers.getPFHeader(Headers.NODE_NAME),
                topology_id: headers.getPFHeader(Headers.TOPOLOGY_ID),
            };

            const cm = new CounterMessage(
                node,
                headers.getRaw(),
                resultCode,
                content.result.message,
                parseInt(content.route.following, 10),
                parseInt(content.route.multiplier, 10),
            );

            logger.info(`Counter message received with status: "${resultCode}"`, {
                topology_id: cm.getTopologyId(),
                node_id: cm.getNodeId(),
                correlation_id: cm.getCorrelationId(),
                process_id: cm.getProcessId(),
                parent_id: cm.getParentId(),
            });

            this.updateProcessInfo(cm);
        } catch (e) {
            logger.error("Cannot handle counter message.", {error: e});
        }

        return;
    }

    /**
     *
     * @param {CounterMessage} cm
     * @return {void}
     */
    private async updateProcessInfo(cm: CounterMessage): Promise<void> {
        const topologyId = cm.getTopologyId();
        const processId = cm.getProcessId();

        let processInfo: ICounterProcessInfo = await this.storage.get(topologyId, processId);

        if (!processInfo) {
            processInfo = CounterProcess.createProcessInfo(topologyId, cm);
        }

        processInfo = CounterProcess.updateProcessInfo(processInfo, cm);

        if (CounterProcess.isProcessFinished(processInfo)) {
            processInfo.end_timestamp = Date.now();
            this.onJobFinished(processInfo);
            this.storage.remove(topologyId, processId);
        } else {
            const added = await this.storage.add(topologyId, processInfo);
            if (!added) {
                logger.error(`Could not add to counter storage. ${JSON.stringify(processInfo)}`);
            }
        }
    }

    /**
     * Publish message informing that job is completed
     *
     * @param process
     */
    private onJobFinished(process: ICounterProcessInfo): void {
        if (!process) {
            logger.warn(`Counter onJobFinished received invalid process info data: "${process}"`);
            return;
        }

        const e = this.settings.pub.exchange;
        const rKey = this.settings.pub.routing_key;
        const options: Options.Publish = {
            contentType: "application/json",
        };

        this.publisher.publish(e.name, rKey, new Buffer(JSON.stringify(process)), options)
            .then(() => {
                logger.info(
                    "Counter job evaluated as finished",
                    {node_id: "counter", correlation_id: process.correlation_id, process_id: process.process_id},
                );

                const duration = process.end_timestamp - process.start_timestamp;

                this.metrics.send(
                    {
                        counter_process_result: process.ok === process.total,
                        counter_process_duration: duration,
                        counter_process_ok_count: process.ok,
                        counter_process_fail_count: process.nok,
                    })
                    .catch((err) => {
                        logger.warn("Unable to send counter metrics.", {
                            error: err,
                            node_id: "counter",
                            correlation_id: process.correlation_id,
                            process_id: process.process_id,
                        });
                    });

                this.terminator.tryTerminate(process.topology);
            });
    }

}
