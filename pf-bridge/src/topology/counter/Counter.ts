import { Channel, Message } from "amqplib";
import Connection from "lib-nodejs/dist/src/rabbitmq/Connection";
import Publisher from "lib-nodejs/dist/src/rabbitmq/Publisher";
import logger from "../../logger/Logger";
import {default as CounterMessage, ICounterMessageContent, ICounterMessageHeaders} from "../../message/CounterMessage";
import { ResultCode } from "../../message/ResultCode";
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
    id: string;
    total: number;
    ok: number;
    nok: number;
    messages: ICounterLog[];
}

/**
 * Topology component that receives signals(messages) and watches if some process run throught whole topology
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
     * @param {string} id
     * @return {ICounterProcessInfo}
     * @private
     */
    private static createJob(topology: string, id: string): ICounterProcessInfo {
        return {
            topology,
            id,
            total: 1,
            ok: 0,
            nok: 0,
            messages: [],
        };
    }

    /**
     *
     * @param {ICounterProcessInfo} processInfo
     * @param {ResultCode} result
     * @param {number} following
     * @param {number} multiplier
     * @param {ICounterLog} log
     * @return {ICounterProcessInfo}
     */
    private static updateJob(
        processInfo: ICounterProcessInfo,
        result: ResultCode,
        following: number = 0,
        multiplier: number = 1,
        log?: ICounterLog,
    ): ICounterProcessInfo {
        if (result === ResultCode.SUCCESS) {
            processInfo.ok += 1;
        } else {
            processInfo.nok += 1;
        }

        processInfo.total += multiplier * following;

        if (log) {
            processInfo.messages.push(log);
        }

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

    /**
     *
     * @param settings
     * @param connection
     */
    constructor(settings: ICounterSettings, connection: Connection) {
        this.processes = {};
        this.settings = settings;
        this.connection = connection;
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
        let headers: ICounterMessageHeaders = null;
        let content: any;

        try {
            headers = msg.properties.headers;
            content = JSON.parse(msg.content.toString());

            const processId = Counter.getMostTopProcessId(headers.process_id);
            const resultCode = content.result.code;

            const cm = new CounterMessage(
                headers.node_id,
                headers.correlation_id,
                processId,
                headers.parent_id,
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

            this.handleCounterMessage(cm);
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
    private handleCounterMessage(cm: CounterMessage): ICounterProcessInfo {
        const log: ICounterLog = { node: cm.getNodeId(), resultCode: cm.getResultCode(), message: cm.getResultMsg()};

        let proc: ICounterProcessInfo = this.processes[cm.getProcessId()] ? this.processes[cm.getProcessId()] : null;

        if (!proc) {
            proc = Counter.createJob(this.settings.topology, cm.getProcessId());
        }

        proc = Counter.updateJob(proc, cm.getResultCode(), cm.getFollowing(), cm.getMultiplier(), log);

        if (Counter.isProcessFinished(proc)) {
            this.onJobFinished(this.processes[cm.getProcessId()]);
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
        this.publisher.publish(e.name, rKey, new Buffer(JSON.stringify(process)), {})
            .then(() => {
                logger.info(
                    "Counter job evaluated as finished",
                    { node_id: "counter", correlation_id: process.id },
                );
            });
    }

}
