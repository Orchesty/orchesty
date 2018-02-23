import {Channel, Message, Options} from "amqplib";
import {Connection} from "amqplib-plus/dist/lib/Connection";
import {Publisher} from "amqplib-plus/dist/lib/Publisher";
import {IMetrics} from "metrics-sender/dist/lib/metrics/IMetrics";
import IStoppable from "../IStoppable";
import logger from "../logger/Logger";
import {default as CounterMessage} from "../message/CounterMessage";
import Headers from "../message/Headers";
import Terminator from "../terminator/Terminator";
import {INodeLabel} from "../topology/Configurator";
import CounterConsumer from "./CounterConsumer";
import {default as CounterProcess, ICounterProcessInfo} from "./CounterProcess";
import Distributor from "./distributor/Distributor";
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
export default class Counter implements ICounter, IStoppable {

    /**
     * Creates CounterMessage object from AMQPMessage object
     * @param {Message} msg
     * @return {CounterMessage}
     */
    private static createCounterMessage(msg: Message): CounterMessage {
        const headers = new Headers(msg.properties.headers);
        const content = JSON.parse(msg.content.toString());
        const processId = CounterProcess.getMostTopProcessId(headers.getPFHeader(Headers.PROCESS_ID));
        headers.setPFHeader(Headers.PROCESS_ID, processId);
        const node: INodeLabel = headers.createNodeLabel();

        return new CounterMessage(
            node,
            headers.getRaw(),
            content.result.code,
            content.result.message,
            parseInt(content.route.following, 10),
            parseInt(content.route.multiplier, 10),
        );
    }

    private settings: any;
    private connection: Connection;
    private publisher: Publisher;
    private consumer: CounterConsumer;
    private storage: ICounterStorage;
    private terminator: Terminator;
    private metrics: IMetrics;
    private consumerTag: string;
    private distributor: Distributor;

    /**
     *
     * @param settings
     * @param connection
     * @param storage
     * @param distributor
     * @param terminator
     * @param metrics
     */
    constructor(
        settings: ICounterSettings,
        connection: Connection,
        storage: ICounterStorage,
        distributor: Distributor,
        terminator: Terminator,
        metrics: IMetrics,
    ) {
        this.settings = settings;
        this.connection = connection;
        this.storage = storage;
        this.distributor = distributor;
        this.terminator = terminator;
        this.metrics = metrics;

        this.prepareConsumer();
        this.preparePublisher();
    }

    /**
     *
     * @return {ICounterSettings}
     */
    public getSettings(): ICounterSettings {
        return this.settings;
    }

    /**
     * Listen to the event stream and keep info about job partial results
     * On job end, send process end message.
     */
    public async start(): Promise<void> {
        const inQueue = this.settings.sub.queue;
        this.consumerTag = await this.consumer.consume(inQueue.name, inQueue.options);

        await this.terminator.startServer();

        logger.info(`Counter started consuming messages from "${inQueue.name}" queue, consumerTag ${this.consumerTag}`);
    }

    /**
     * Stops consuming queue and wait some time to complete persisting etc.
     *
     * @return {Promise<void>}
     */
    public async stop(): Promise<void> {
        await this.consumer.cancel(this.consumerTag);

        // Give process some time to finish processing current data
        await new Promise((resolve) => setTimeout(resolve, 100));

        await this.storage.stop();

        return;
    }

    /**
     * Creates subscription channel
     */
    private prepareConsumer() {
        const prepareFn: any = async (ch: Channel) => {
            const s = this.settings;

            await ch.assertQueue(s.sub.queue.name, s.sub.queue.options);
            await ch.prefetch(s.sub.queue.prefetch);
        };

        this.consumer = new CounterConsumer(
            this.connection,
            prepareFn,
            async (msg: Message) => await this.handleMessage(msg),
        );
    }

    /**
     * Creates publish channel
     */
    private preparePublisher() {
        const prepareFn: any = async (ch: Channel) => {
            const pubExSett = this.settings.pub.exchange;
            const pubQSett = this.settings.pub.queue;

            await Promise.all([
                ch.assertExchange(pubExSett.name, pubExSett.type, pubExSett.options),
                ch.assertQueue(pubQSett.name, pubQSett.options),
            ]);

            await ch.bindQueue(pubQSett.name, pubExSett.name, this.settings.pub.routing_key);
        };

        this.publisher = new Publisher(this.connection, prepareFn);
    }

    /**
     *
     * @param {Message} msg
     * @return {Promise<any>}
     */
    private async handleMessage(msg: Message): Promise<any> {
        try {
            const cm = Counter.createCounterMessage(msg);

            logger.info(`Counter message received: "${cm.toString()}"`, {
                topology_id: cm.getTopologyId(),
                node_id: cm.getNodeId(),
                correlation_id: cm.getCorrelationId(),
                process_id: cm.getProcessId(),
                parent_id: cm.getParentId(),
            });

            return new Promise((resolve, reject) => {
                this.distributor.add(cm.getTopologyId(), cm.getProcessId(), {msg: cm, resolve, reject});

                if (this.distributor.length(cm.getTopologyId(), cm.getProcessId()) === 1) {
                    this.handleQueue(cm.getTopologyId(), cm.getProcessId());
                }
            });
        } catch (e) {
            logger.error("Cannot create counter message from amqp message.", {error: e});

            return Promise.reject(e);
        }
    }

    /**
     * Recursively process messages in synchronous way
     *
     */
    private handleQueue(topoId: string, processId: string): void {
        if (this.distributor.has(topoId, processId) === false) {
            return;
        }

        if (this.distributor.length(topoId, processId) === 0) {
            this.distributor.deleteSoftly(topoId, processId);
            return;
        }

        const first = this.distributor.first(topoId, processId);

        if (typeof first === "undefined") {
            return;
        }

        (async () => {
            try {
                await this.updateProcessInfo(first.msg);
                first.resolve();
                this.distributor.shift(topoId, processId);
                this.handleQueue(topoId, processId);
            } catch (e) {
                first.reject(e);
            }
        })();
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
            await this.onJobFinished(processInfo);
            await this.storage.remove(topologyId, processId);
            return Promise.resolve();
        } else {
            await this.storage.add(topologyId, processInfo);
            return Promise.resolve();
        }
    }

    /**
     * Publish message informing that job is completed
     *
     * @param process
     */
    private async onJobFinished(process: ICounterProcessInfo): Promise<void> {
        if (!process) {
            logger.warn(`Counter onJobFinished received invalid process info data: "${process}"`);
            return;
        }

        this.publishResult(process);
        this.terminator.tryTerminate(process.topology);

        this.logFinished(process);
        this.sendMetrics(process);
    }

    /**
     *
     * @param {ICounterProcessInfo} process
     * @return {Promise<void>}
     */
    private async publishResult(process: ICounterProcessInfo): Promise<void> {
        const ex = this.settings.pub.exchange;
        const rKey = this.settings.pub.routing_key;
        const options: Options.Publish = { contentType: "application/json" };

        await this.publisher.publish(ex.name, rKey, new Buffer(JSON.stringify(process)), options);
    }

    /**
     *
     * @param {ICounterProcessInfo} process
     * @return {Promise<void>}
     */
    private async sendMetrics(process: ICounterProcessInfo): Promise<void> {
        try {
            this.metrics.removeTag("node_id");
            this.metrics.addTag("topology_id", process.topology.split("-")[0]);
            const metricMsg = await this.metrics.send({
                counter_process_result: process.ok === process.total,
                counter_process_duration: process.end_timestamp - process.start_timestamp,
                counter_process_ok_count: process.ok,
                counter_process_fail_count: process.nok,
            }, true);
            logger.info(metricMsg);
        } catch (e) {
            logger.warn("Unable to send counter metrics.", {
                error: e,
                node_id: "counter",
                correlation_id: process.correlation_id,
                process_id: process.process_id,
            });
        }
    }

    /**
     *
     * @param {ICounterProcessInfo} process
     */
    private logFinished(process: ICounterProcessInfo): void {
        logger.info(
            `Counter job evaluated as finished. Status: ${process.success}`,
            {
                node_id: "counter",
                correlation_id: process.correlation_id,
                process_id: process.process_id,
                topology_id: process.topology,
            },
        );
    }

}
