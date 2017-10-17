import { Channel, Message } from "amqplib";
import IMetrics from "lib-nodejs/dist/src/metrics/IMetrics";
import Connection from "lib-nodejs/dist/src/rabbitmq/Connection";
import Publisher from "lib-nodejs/dist/src/rabbitmq/Publisher";
import logger from "../../logger/Logger";
import {default as CounterMessage} from "../../message/CounterMessage";
import Headers from "../../message/Headers";
import { ResultCode } from "../../message/ResultCode";
import {INodeLabel} from "../Configurator";
import CounterConsumer from "./CounterConsumer";

const ID_DELIMITER = ".";

export interface ICounterSettings {
    topology: string;
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

interface ICounterLog {
    resultCode: ResultCode;
    node: string;
    message: string;
}

export interface ICounterProcessInfo {
    topology: string;
    correlation_id: string;
    process_id: string;
    total: number;
    ok: number;
    nok: number;
    messages: ICounterLog[];
    start_timestamp: number;
    end_timestamp: number;
}

/**
 * Topology component that receives signals(messages) and watches if some process run through whole topology
 * If yes, it sends process finished message
 */
export default class Counter {

    /**
     * Returns top parent of job
     * @param {string} id
     * @return {string}
     * @private
     */
    private static getMostTopProcessId(id: string) {
        const stringId = `${id}`;
        const parts = stringId.split(ID_DELIMITER, 1);

        return parts[0];
    }

    /**
     *
     * @param {string} topology
     * @param {CounterMessage} cm
     * @return {ICounterProcessInfo}
     */
    private static createProcessInfo(topology: string, cm: CounterMessage): ICounterProcessInfo {
        return {
            topology,
            correlation_id: cm.getCorrelationId(),
            process_id: cm.getProcessId(),
            total: 1,
            ok: 0,
            nok: 0,
            messages: [],
            start_timestamp: Date.now(),
            end_timestamp: 0,
        };
    }

    /**
     *
     * @param {ICounterProcessInfo} processInfo
     * @param {CounterMessage} cm
     * @return {ICounterProcessInfo}
     */
    private static updateProcessInfo(processInfo: ICounterProcessInfo, cm: CounterMessage): ICounterProcessInfo {
        if (cm.getResultCode() === ResultCode.SUCCESS) {
            processInfo.ok += 1;
        } else {
            processInfo.nok += 1;
        }

        processInfo.total += cm.getMultiplier() * cm.getFollowing();

        const log: ICounterLog = { node: cm.getNodeId(), resultCode: cm.getResultCode(), message: cm.getResultMsg()};
        processInfo.messages.push(log);

        return processInfo;
    }

    /**
     * Returns true if process is completely finished
     *
     * @param {ICounterProcessInfo} job
     * @return {boolean}
     * @private
     */
    private static isProcessFinished(job: ICounterProcessInfo) {
        if (job.nok + job.ok === job.total) {
            return true;
        }
        return false;
    }

    private processes: { [key: string]: ICounterProcessInfo };
    private settings: any;
    private connection: Connection;
    private publisher: Publisher;
    private consumer: CounterConsumer;
    private metrics: IMetrics;

    /**
     *
     * @param settings
     * @param connection
     * @param metrics
     */
    constructor(settings: ICounterSettings, connection: Connection, metrics: IMetrics) {
        this.processes = {};
        this.settings = settings;
        this.connection = connection;
        this.metrics = metrics;
        this.prepareConsumer();
        this.preparePublisher();
    }

    /**
     * Listen to the event stream and keep info about job partial results
     * On job end, send process end message.
     */
    public listen(): Promise<void> {
        return this.consumer.consume(this.settings.sub.queue.name, this.settings.sub.queue.options)
            .then(() => {
                logger.info(`Counter started consuming messages from "${this.settings.sub.queue.name}" queue`);
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

        this.consumer = new CounterConsumer(this.connection, prepareFn, (msg: Message) => { this.handleMessage(msg); });
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
            const processId = Counter.getMostTopProcessId(headers.getPFHeader(Headers.PROCESS_ID));
            headers.setPFHeader(Headers.PROCESS_ID, processId);

            const node: INodeLabel = {
                id: headers.getHeader(Headers.NODE_ID),
                node_id: headers.getHeader(Headers.NODE_ID),
                node_name: headers.getHeader(Headers.NODE_NAME),
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
                node_id: cm.getNodeId(),
                correlation_id: cm.getCorrelationId(),
                process_id: cm.getProcessId(),
                parent_id: cm.getParentId(),
            });

            this.updateProcessInfo(cm);
        } catch (e) {
            logger.error("Invalid counter message.", { error: e });
        }

        return;
    }

    /**
     *
     * @param {CounterMessage} cm
     * @return {ICounterProcessInfo}
     */
    private updateProcessInfo(cm: CounterMessage): ICounterProcessInfo {
        let proc: ICounterProcessInfo = this.processes[cm.getProcessId()] ? this.processes[cm.getProcessId()] : null;

        if (!proc) {
            proc = Counter.createProcessInfo(this.settings.topology, cm);
        }

        proc = Counter.updateProcessInfo(proc, cm);

        if (Counter.isProcessFinished(proc)) {
            proc.end_timestamp = Date.now();
            this.onJobFinished(proc);
            delete this.processes[cm.getProcessId()];
        } else {
            // save job
            this.processes[cm.getProcessId()] = proc;
        }

        return proc;
    }

    /**
     * Publish message informing that job is completed
     *
     * @param process
     */
    private onJobFinished(process: ICounterProcessInfo): void {
        const e = this.settings.pub.exchange;
        const rKey = this.settings.pub.routing_key;

        if (!process) {
            logger.warn(`Counter onJobFinished received invalid process info data: "${process}"`);
            return;
        }

        this.publisher.publish(e.name, rKey, new Buffer(JSON.stringify(process)), {})
            .then(() => {
                logger.info(
                    "Counter job evaluated as finished",
                    { node_id: "counter", correlation_id: process.correlation_id, process_id: process.process_id },
                );

                const duration = process.end_timestamp - process.start_timestamp;

                this.metrics.send(
                    {
                        process_result: process.ok === process.total,
                        process_total_duration: duration,
                        process_nodes_ok: process.ok,
                        process_nodes_nok: process.nok,
                    })
                    .catch((err) => {
                        logger.warn("Unable to send counter metrics.", {
                            error: err,
                            node_id: "counter",
                            correlation_id: process.correlation_id,
                            process_id: process.process_id,
                        });
                    });
            });
    }

}
