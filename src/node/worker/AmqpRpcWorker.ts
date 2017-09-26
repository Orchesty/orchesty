import {Channel, Message} from "amqplib";
import Container from "lib-nodejs/dist/src/container/Container";
import Connection from "lib-nodejs/dist/src/rabbitmq/Connection";
import Publisher from "lib-nodejs/dist/src/rabbitmq/Publisher";
import SimpleConsumer from "lib-nodejs/dist/src/rabbitmq/SimpleConsumer";
import logger from "../../logger/Logger";
import JobMessage from "../../message/JobMessage";
import {ResultCode} from "../../message/ResultCode";
import IWorker from "./IWorker";

export interface IAmqpRpcWorkerSettings {
    node_id: string;
    publish_queue: {
        name: string;
        options: any;
    };
}

interface IWaiting {
    resolveFn: any;
    message: JobMessage;
    sequence: number;
}

export const TEST_TYPE = "test";
export const BATCH_REQUEST_TYPE = "batch";
export const BATCH_END_TYPE = "batch_end";
export const BATCH_ITEM_TYPE = "batch_item";

/**
 * TODO add waiting timeout
 * TODO if in responses are messages with same sequenceId take the latest
 */
class AmqpRpcWorker implements IWorker {

    private publisher: Publisher;
    private resultsQueue: { name: string, options: any, prefetch: number };
    private resultsConsumer: SimpleConsumer;

    private waiting: Container;

    /**
     *
     * @param {AMQPConnection} connection
     * @param {IAmqpRpcWorkerSettings} settings
     */
    constructor(private connection: Connection, private settings: IAmqpRpcWorkerSettings) {
        this.waiting = new Container();
        this.resultsQueue = {
            name: `${settings.publish_queue.name}_reply`,
            options: settings.publish_queue.options,
            prefetch: 1,
        };

        const publisherPrepare = (ch: Channel): Promise<void> => {
            const q = settings.publish_queue;

            return new Promise((resolve) => {
                ch.assertQueue(q.name, q.options)
                    .then(() => {
                        resolve();
                    });
            });
        };

        const resultsConsumerPrepare = (ch: Channel): Promise<void> => {
            return new Promise((resolve) => {
                ch.assertQueue(this.resultsQueue.name, this.resultsQueue.options)
                    .then(() => {
                        return ch.prefetch(this.resultsQueue.prefetch);
                    })
                    .then(() => {
                        resolve();
                    });
            });
        };

        this.publisher = new Publisher(connection, publisherPrepare);
        this.resultsConsumer = new SimpleConsumer(connection, resultsConsumerPrepare, (msg: Message) => {
            this.processRpcResultMessage(msg);
        });
        this.resultsConsumer.consume(this.resultsQueue.name, {})
            .then(() => {
                logger.info(
                    `Worker[type='amqprpc'] consuming ${this.resultsQueue.name}`,
                    { node_id: this.settings.node_id},
                );
            });
    }

    /**
     * Accepts message, returns unsatisfied promise, which should be satisfied later
     *
     * @param {JobMessage} msg
     * @return {Promise<JobMessage>}
     */
    public processData(msg: JobMessage): Promise<JobMessage> {
        this.publisher.sendToQueue(
            this.settings.publish_queue.name,
            new Buffer(msg.getContent()),
            {
                replyTo: this.resultsQueue.name,
                correlationId: msg.getJobId(),
                type: BATCH_REQUEST_TYPE,
                headers: {
                    node_id: this.settings.node_id,
                },
            },
        );

        if (this.waiting.has(msg.getJobId())) {
            logger.error(
                `Worker[type'amqprpc'] is already waiting for results with same id.`,
                { node_id: this.settings.node_id, correlation_id: msg.getJobId() },
            );

            msg.setResult({
                status: ResultCode.MESSAGE_ALREADY_BEING_PROCESSED,
                message: `Message[id=${msg.getJobId()}] is already being processed.`,
            });

            return Promise.resolve(msg);
        }

        // resolve function will be called with the last received result message
        return new Promise((resolve) => {
            const w: IWaiting = { resolveFn: resolve, message: msg, sequence: 0 };
            this.waiting.set(msg.getJobId(), w);
        });

    }

    /**
     * Checks whether the worker counter-side is ready by sending test message and expecting result
     * @return {Promise<boolean>}
     */
    public isWorkerReady(): Promise<boolean> {
        return new Promise((resolve) => {
            const testId = "worker.amqprpc.test";

            const resolveTestFn = (msg: JobMessage) => {
                if (msg.getJobId() === testId) {
                    resolve(true);
                } else {
                    resolve(false);
                }
            };

            const jobMsg = new JobMessage(testId, testId, 1, {}, "");
            const t: IWaiting = { resolveFn: resolveTestFn, message: jobMsg, sequence: 0 };
            this.waiting.set(testId, t);

            this.publisher.sendToQueue(
                this.settings.publish_queue.name,
                new Buffer("isWorkerReady test message"),
                {
                    type: TEST_TYPE,
                    correlationId: testId,
                    replyTo: this.resultsQueue.name,
                    headers: {
                        node_id: this.settings.node_id,
                    },
                },
            );
        });
    }

    /**
     * Handler for received result message decides what to do with it.
     *
     * @param {Message} msg
     */
    private processRpcResultMessage(msg: Message): void {
        const corrId = msg.properties.correlationId;

        if (!this.waiting.has(corrId)) {
            logger.warn(
                `Worker[type='amqprpc'] received result with non-existing corrId`,
                { node_id: this.settings.node_id, correlation_id: corrId },
            );

            return;
        }

        switch (msg.properties.type) {
            case BATCH_ITEM_TYPE:
                this.updateWaiting(corrId, msg);
                break;
            case BATCH_END_TYPE:
                this.resolveWaiting(corrId);
                break;
            case TEST_TYPE:
                this.resolveWaiting(corrId);
                break;
            default:
                logger.warn(
                    `Worker[type='amqprpc'] received result of unknown type: ${msg.properties.type}`,
                    { node_id: this.settings.node_id, correlation_id: corrId },
                );
        }
    }

    /**
     * Updates the JobMessage object stored in memory
     *
     * @param {string} corrId
     * @param {Message} resultMsg
     */
    private updateWaiting(corrId: string, resultMsg: Message) {
        const stored: IWaiting = this.waiting.get(corrId);
        stored.sequence++;

        const origContent = JSON.parse(stored.message.getContent());
        const newContent = JSON.parse(resultMsg.content.toString());

        const splitMsg = new JobMessage(
            stored.message.getJobId(),
            stored.sequence,
            JSON.parse(JSON.stringify(stored.message.getHeaders())), // simple object cloning,
            JSON.stringify({ data: newContent.data, settings: origContent.settings}),
            { status: ResultCode.SUCCESS, message: `Part ${stored.sequence}` },
        );

        stored.message.addSplit(splitMsg);
    }

    /**
     * Resolves the stored promise with populated message
     * @param {string} corrId
     */
    private resolveWaiting(corrId: string) {
        const stored: IWaiting = this.waiting.get(corrId);
        stored.resolveFn(stored.message);

        this.waiting.delete(corrId);
    }

}

export default AmqpRpcWorker;
