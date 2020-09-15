import {Channel, Message, Options} from "amqplib";
import {Connection, Publisher} from "amqplib-plus";
import {IMetrics} from "metrics-sender/dist/lib/metrics/IMetrics";
import IStoppable from "../IStoppable";
import logger from "../logger/Logger";
import {default as CounterMessage} from "../message/CounterMessage";
import Headers from "../message/Headers";
import {ResultCode} from "../message/ResultCode";
import Terminator from "../terminator/Terminator";
import {INodeLabel} from "../topology/Configurator";
import CounterConsumer from "./CounterConsumer";
import {default as CounterProcess, ICounterProcessInfo} from "./CounterProcess";
import Distributor from "./distributor/Distributor";
import ICounter from "./ICounter";
import ICounterStorage from "./storage/ICounterStorage";
import {MongoProgressStorage} from "./storage/MongoProgressStorage";

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
            content.result.originalCode,
            content.result.request,
            content.result.response,
        );
    }

    private static isSkippable(cm: CounterMessage): boolean {
        return cm.getResultCode() === ResultCode.SUCCESS &&
            cm.getFollowing() === 1 &&
            cm.getMultiplier() === 1 &&
            cm.isFromStartingPoint() === false;
    }

    private settings: any;
    private connection: Connection;
    private publisher: Publisher;
    private consumer: CounterConsumer;
    private storage: ICounterStorage;
    private terminator: Terminator;
    private metrics: IMetrics;
    private consumerTag: string;
    private readBuffer: Distributor;
    private progressStorage: MongoProgressStorage;

    constructor(
        settings: ICounterSettings,
        connection: Connection,
        storage: ICounterStorage,
        readBuffer: Distributor,
        terminator: Terminator,
        metrics: IMetrics,
        progressStorage: MongoProgressStorage
    ) {
        this.settings = settings;
        this.connection = connection;
        this.storage = storage;
        this.readBuffer = readBuffer;
        this.terminator = terminator;
        this.metrics = metrics;
        this.progressStorage = progressStorage;

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

            logger.debug(
                "Counter message received.",
                {correlation_id: cm.getCorrelationId(), topology_id: cm.getTopologyId(), data: cm.toString()},
            );

            // optimization: skip evaluating success messages with only 1 follower
            if (Counter.isSkippable(cm)) {
                return Promise.resolve(true);
            }

            return new Promise((resolve, reject) => {
                this.readBuffer.add(cm.getTopologyId(), cm.getCorrelationId(), {msg: cm, resolve, reject});

                // length === 1 means that is there just the record added above so we can process it immediately
                if (this.readBuffer.length(cm.getTopologyId(), cm.getCorrelationId()) === 1) {
                    this.processReadBuffer(cm.getTopologyId(), cm.getCorrelationId());
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
    private processReadBuffer(topoId: string, id: string): void {
        if (this.readBuffer.has(topoId, id) === false) {
            return;
        }

        if (this.readBuffer.length(topoId, id) === 0) {
            this.readBuffer.deleteSoftly(topoId, id);
            return;
        }

        const first = this.readBuffer.first(topoId, id);

        if (typeof first === "undefined") {
            return;
        }

        (async () => {
            try {
                await this.updateProcessInfo(first.msg);
                this.readBuffer.shift(topoId, id);
                first.resolve();
                this.processReadBuffer(topoId, id);
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
            await this.onJobFinished(processInfo, cm);
            await this.storage.remove(topologyId, processId);
            await this.progressStorage.upsertProgress(cm, processInfo.end_timestamp, processInfo.success ? 'OK' : 'NOK');
        } else {
            await this.storage.add(topologyId, processInfo);
            await this.progressStorage.upsertProgress(cm);
        }

        return Promise.resolve();
    }

    /**
     * Publish message informing that job is completed
     *
     * @param process
     * @param cm
     */
    private async onJobFinished(process: ICounterProcessInfo, cm: CounterMessage): Promise<void> {
        if (!process) {
            logger.error(`Counter onJobFinished received invalid process info data: "${process}"`);
            return;
        }

        if (process.parent_id !== "") {
            return await this.evaluateParent(process, cm);
        }

        await this.publishResult(process);
        await this.terminator.tryTerminate(process.topology);

        await this.notify(process);
    }

    /**
     *
     * @param {ICounterProcessInfo} process
     * @param {CounterMessage} cm
     * @return {Promise<void>}
     */
    private async evaluateParent(process: ICounterProcessInfo, cm: CounterMessage): Promise<void> {
        // make object copy and change id to fake parental counter message
        const headers = new Headers(cm.getHeaders().getRaw());
        headers.setPFHeader(Headers.PARENT_ID, "");
        headers.setPFHeader(Headers.PROCESS_ID, process.parent_id);

        return await this.updateProcessInfo(new CounterMessage(
            cm.getNodeLabel(),
            headers.getRaw(),
            process.success === true ? ResultCode.SUCCESS : ResultCode.CHILD_PROCESS_ERROR,
            cm.getResultMsg(),
            0,
            1,
            cm.getOriginalResultCode(),
            cm.getRequest(),
            cm.getResponse(),
        ));
    }

    /**
     *
     * @param {ICounterProcessInfo} process
     * @return {Promise<void>}
     */
    private async publishResult(process: ICounterProcessInfo): Promise<void> {
        const ex = this.settings.pub.exchange;
        const rKey = this.settings.pub.routing_key;
        const options: Options.Publish = {contentType: "application/json"};

        await this.publisher.publish(ex.name, rKey, Buffer.from(JSON.stringify(process)), options);
    }

    /**
     *
     * @param {ICounterProcessInfo} process
     * @return {Promise<void>}
     */
    private async notify(process: ICounterProcessInfo): Promise<void> {
        try {
            this.metrics.removeTag("node_id");
            this.metrics.addTag("topology_id", process.topology.split("-")[0]);
            await this.metrics.send({
                counter_process_result: process.ok === process.total,
                counter_process_duration: process.end_timestamp - process.start_timestamp,
                counter_process_ok_count: process.ok,
                counter_process_fail_count: process.nok,
            }, true);
        } catch (e) {
            logger.error("Unable to send counter metrics.", {
                error: e,
                node_id: "counter",
                correlation_id: process.correlation_id,
                process_id: process.process_id,
            });
        }

        logger.debug(
            `Finished process [processId='${process.process_id}]', parentId='${process.parent_id}'`,
            {
                node_id: "counter",
                correlation_id: process.correlation_id,
                process_id: process.process_id,
                parent_id: process.parent_id,
                topology_id: process.topology,
                data: JSON.stringify({total: process.total, ok_count: process.ok, nok_count: process.nok}),
            },
        );
    }

}
