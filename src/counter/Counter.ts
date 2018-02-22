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

interface ISyncObject {
    msg: CounterMessage;
    resolve: any;
    reject: any;
}

interface IProcessesSyncMap {
    [key: string]: ISyncObject[];
}

interface ITopologiesSyncMap {
    [key: string]: IProcessesSyncMap;
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
    private syncQueue: ITopologiesSyncMap;

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

        this.syncQueue = {};

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

                if (!this.syncQueue[cm.getTopologyId()]) {
                    this.syncQueue[cm.getTopologyId()] = {};
                }

                if (!this.syncQueue[cm.getTopologyId()][cm.getProcessId()]) {
                    this.syncQueue[cm.getTopologyId()][cm.getProcessId()] = [];
                }

                this.syncQueue[cm.getTopologyId()][cm.getProcessId()].push({msg: cm, resolve, reject});

                if (this.syncQueue[cm.getTopologyId()][cm.getProcessId()].length === 1) {
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
        if (!this.syncQueue[topoId]) {
            return;
        }

        if (!this.syncQueue[topoId][processId]) {
            return;
        }

        if (this.syncQueue[topoId][processId].length === 0) {
            delete this.syncQueue[topoId][processId];
            return;
        }

        const first = this.syncQueue[topoId][processId][0];

        (async () => {
            try {
                await this.updateProcessInfo(first.msg);
                first.resolve();
                this.syncQueue[topoId][processId].shift();
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

        const e = this.settings.pub.exchange;
        const rKey = this.settings.pub.routing_key;
        const options: Options.Publish = {
            contentType: "application/json",
        };

        await this.publisher.publish(e.name, rKey, new Buffer(JSON.stringify(process)), options);

        logger.info(
            `Counter job evaluated as finished. Status: ${process.success}`,
            {
                node_id: "counter",
                correlation_id: process.correlation_id,
                process_id: process.process_id,
                topology_id: process.topology,
            },
        );

        this.terminator.tryTerminate(process.topology);

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

}
