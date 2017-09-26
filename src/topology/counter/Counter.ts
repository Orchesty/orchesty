import { Channel, Message } from "amqplib";
import Connection from "lib-nodejs/dist/src/rabbitmq/Connection";
import Publisher from "lib-nodejs/dist/src/rabbitmq/Publisher";
import * as assert from "power-assert";
import logger from "../../logger/Logger";
import { ICounterMessageContent } from "../../message/CounterMessage";
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

export interface ICounterJobInfo {
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
    private static getTopJobId(id: string) {
        const stringId = `${id}`;
        const parts = stringId.split(ID_DELIMITER, 1);

        return parts[0];
    }

    /**
     *
     * @param {string} topology
     * @param {string} id
     * @return {ICounterJobInfo}
     * @private
     */
    private static createJob(topology: string, id: string): ICounterJobInfo {
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
     * @param {ICounterJobInfo} job
     * @param {ResultCode} result
     * @param {number} following
     * @param {number} multiplier
     * @param {ICounterLog} log
     * @return {ICounterJobInfo}
     */
    private static updateJob(
        job: ICounterJobInfo,
        result: ResultCode,
        following: number = 0,
        multiplier: number = 1,
        log?: ICounterLog,
    ) {
        if (result === ResultCode.SUCCESS) {
            job.ok += 1;
        } else {
            job.nok += 1;
        }

        job.total += multiplier * following;

        if (log) {
            job.messages.push(log);
        }

        return job;
    }

    /**
     * Returns true if process is completely finished
     *
     * @param {ICounterJobInfo} job
     * @return {boolean}
     * @private
     */
    private static isJobFinished(job: ICounterJobInfo) {
        if (job.nok + job.ok === job.total) {
            return true;
        }
        return false;
    }

    private jobs: { [key: string]: ICounterJobInfo };
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
        this.jobs = {};
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
        let headers: {job_id: string, node_id: string} = null;
        let content: ICounterMessageContent = null;

        try {
            headers = msg.properties.headers;
            assert(headers.job_id, 'Missing "job_id" header field.');
            assert(headers.node_id, 'Missing "node_id" header field.');

            content = JSON.parse(msg.content.toString());

            const jobId = Counter.getTopJobId(headers.job_id);
            const node = headers.node_id;
            const resultCode = content.result.code;
            const following = content.route.following;
            const multiplier = content.route.multiplier;
            const log: ICounterLog = { resultCode, node, message: content.result.message };

            logger.info("Counter message received", { node_id: "counter", correlation_id: jobId });

            this.handleJob(jobId, resultCode, following, multiplier, log);
        } catch (e) {
            logger.error("Invalid counter message.", { node_id: "counter", error: e });
        }

        return;
    }

    /**
     *
     * @param {string} jobId
     * @param {number} status
     * @param {number} following
     * @param {number} multiplier
     * @param {ICounterLog} log
     * @return {ICounterJobInfo}
     */
    private handleJob(
        jobId: string,
        status: number,
        following: number,
        multiplier: number,
        log: ICounterLog,
    ): ICounterJobInfo {
        let job = this.jobs[jobId] ? this.jobs[jobId] : null;
        if (job) {
            job = Counter.updateJob(job, status, following, multiplier, log);
        } else {
            job = Counter.createJob(this.settings.topology, jobId);
            job = Counter.updateJob(job, status, following, multiplier, log);
        }

        if (Counter.isJobFinished(job)) {
            this.onJobFinished(this.jobs[jobId]);
            delete this.jobs[jobId];
        } else {
            // save job
            this.jobs[jobId] = job;
        }

        return job;
    }

    /**
     * Publish message informing that job is completed
     *
     * @param job
     */
    private onJobFinished(job: ICounterJobInfo): void {
        const e = this.settings.pub.exchange;
        const rKey = this.settings.pub.routing_key;
        this.publisher.publish(e.name, rKey, new Buffer(JSON.stringify(job)), {})
            .then(() => {
                logger.info(
                    "Counter job evaluated as finished",
                    { node_id: "counter", correlation_id: job.id },
                );
            });
    }

}
