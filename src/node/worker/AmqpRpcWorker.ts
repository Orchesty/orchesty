import {Channel, Message as AmqpMessage} from "amqplib";
import Container from "lib-nodejs/dist/src/container/Container";
import Connection from "lib-nodejs/dist/src/rabbitmq/Connection";
import Publisher from "lib-nodejs/dist/src/rabbitmq/Publisher";
import SimpleConsumer from "lib-nodejs/dist/src/rabbitmq/SimpleConsumer";
import logger from "../../logger/Logger";
import JobMessage from "../../message/JobMessage";
import {ResultCode} from "../../message/ResultCode";
import IPartialForwarder from "../drain/IPartialForwarder";
import IWorker from "./IWorker";

export interface IAmqpRpcWorkerSettings {
    node_id: string;
    node_name: string;
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
     * @param {IPartialForwarder} partialForwarder
     */
    constructor(
        private connection: Connection,
        private settings: IAmqpRpcWorkerSettings,
        private partialForwarder: IPartialForwarder,
    ) {
        this.waiting = new Container();
        this.resultsQueue = {
            name: `${settings.publish_queue.name}_reply`,
            options: settings.publish_queue.options || { durable: false, exclusive: false, autoDelete: false },
            prefetch: 1,
        };

        const publisherPrepare = (ch: Channel): Promise<void> => {
            const q = settings.publish_queue;

            return new Promise((resolve) => {
                ch.assertQueue(q.name, q.options || { durable: false, exclusive: false, autoDelete: false })
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
        this.resultsConsumer = new SimpleConsumer(connection, resultsConsumerPrepare, (msg: AmqpMessage) => {
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
                type: BATCH_REQUEST_TYPE,
                replyTo: this.resultsQueue.name,
                correlationId: msg.getCorrelationId(),
                headers: {
                    node_id: this.settings.node_id,
                    node_name: this.settings.node_name,
                    correlationId: msg.getCorrelationId(),
                    processId: msg.getProcessId(),
                    parentId: msg.getParentId(),
                    sequenceId: msg.getSequenceId(),
                },
            },
        ).then(() => {
            logger.warn(`Worker[type='amqprpc'] received result with non-existing corrId`, logger.ctxFromMsg(msg));
        }).catch((err: Error) => {
            const context = logger.ctxFromMsg(msg);
            context.error = err;
            logger.error(`Worker[type='amqprpc'] sending message failed`, context);
        });

        if (this.waiting.has(msg.getProcessId())) {
            this.onDuplicateMessage(msg);
            return Promise.resolve(msg);
        }

        // resolve will be done with the last received result message
        return new Promise((resolve) => {
            // Set the original message not to be re-sent itself via classic Drain
            msg.setForwardSelf(false);
            msg.setMultiplier(0);

            const w: IWaiting = { resolveFn: resolve, message: msg, sequence: 0 };
            this.waiting.set(msg.getCorrelationId(), w);
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
                if (msg.getProcessId() === testId) {
                    resolve(true);
                } else {
                    resolve(false);
                }
            };

            const jobMsg = new JobMessage(this.settings.node_id, testId, testId, "", 1, {}, new Buffer(""));
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
                        node_name: testId,
                    },
                },
            );
        });
    }

    /**
     * Handler for received result message decides what to do with it.
     *
     * @param {AmqpMessage} msg
     */
    private processRpcResultMessage(msg: AmqpMessage): void {
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
                this.resolveWaiting(corrId, msg);
                break;
            case TEST_TYPE:
                this.resolveWaiting(corrId, msg);
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
     * @param {AmqpMessage} resultMsg
     */
    private updateWaiting(corrId: string, resultMsg: AmqpMessage): void {
        const stored: IWaiting = this.waiting.get(corrId);
        stored.sequence++;

        const origContent = JSON.parse(stored.message.getContent());
        const newContent = JSON.parse(resultMsg.content.toString());

        const splitMsg = new JobMessage(
            this.settings.node_id,
            stored.message.getCorrelationId(),
            stored.message.getProcessId(),
            stored.message.getParentId(),
            stored.sequence,
            JSON.parse(JSON.stringify(stored.message.getHeaders())), // simple object cloning,
            new Buffer(JSON.stringify({ data: newContent.data, settings: origContent.settings})),

            { code: ResultCode.SUCCESS, message: `Part ${stored.sequence}` },
        );

        stored.message.setMultiplier(stored.message.getMultiplier() + 1);

        this.partialForwarder.forwardPart(splitMsg)
            .catch(() => {
                logger.warn(`Worker[type='amqprpc'] partial forward failed.`, logger.ctxFromMsg(splitMsg));
            });
    }

    /**
     * Resolves the stored promise with populated message
     * @param {string} corrId
     * @param {AmqpMessage} msg
     */
    private resolveWaiting(corrId: string, msg: AmqpMessage): void {
        const stored: IWaiting = this.waiting.get(corrId);
        if (!stored) {
            logger.warn(`Worker[type='amqprpc'] cannot resolve non-existing waiting promise[corrId=${corrId}]`);
            return;
        }

        // Set result according to received headers
        const resCode = msg.properties.headers.hasOwnProperty("result_code") ?
            parseInt(msg.properties.headers.result_code, 10) : ResultCode.MISSING_RESULT_CODE;
        const resMessage = msg.properties.headers.hasOwnProperty("result_message") ?
            msg.properties.headers.result_message : "";

        stored.message.setResult({ code: resCode, message: resMessage });

        // Resolves waiting promise
        stored.resolveFn(stored.message);

        this.waiting.delete(corrId);
    }

    /**
     *
     * @param {JobMessage} msg
     */
    private onDuplicateMessage(msg: JobMessage): void {
        logger.error(
            `Worker[type'amqprpc'] is already waiting for results with same id.`,
            logger.ctxFromMsg(msg),
        );

        msg.setResult({
            code: ResultCode.MESSAGE_ALREADY_BEING_PROCESSED,
            message: `Message[id=${msg.getProcessId()}] is already being processed.`,
        });
    }

}

export default AmqpRpcWorker;
