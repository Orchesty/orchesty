import {Channel, Message as AmqpMessage} from "amqplib";
import {Connection} from "amqplib-plus/dist/lib/Connection";
import {Publisher} from "amqplib-plus/dist/lib/Publisher";
import {SimpleConsumer} from "amqplib-plus/dist/lib/SimpleConsumer";
import {Container} from "hb-utils/dist/lib/Container";
import * as uuid4 from "uuid/v4";
import logger from "../../logger/Logger";
import Headers from "../../message/Headers";
import JobMessage from "../../message/JobMessage";
import {ResultCode} from "../../message/ResultCode";
import {INodeLabel} from "../../topology/Configurator";
import IPartialForwarder from "../drain/IPartialForwarder";
import IWorker from "./IWorker";

export interface IAmqpRpcWorkerSettings {
    node_label: INodeLabel;
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

/**
 * TODO add waiting timeout
 */
class AmqpRpcWorker implements IWorker {

    public static readonly TEST_TYPE = "test";
    public static readonly TEST_ID = "pipes.worker.amqprpc.test";

    public static readonly BATCH_REQUEST_TYPE = "batch";
    public static readonly BATCH_END_TYPE = "batch_end";
    public static readonly BATCH_ITEM_TYPE = "batch_item";

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
            name: `pipes.${settings.node_label.topology_id}.${settings.node_label.id}_reply`,
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
        this.resultsConsumer = new SimpleConsumer(
            connection,
            resultsConsumerPrepare,
            (msg: AmqpMessage) => {
                this.processRpcResultMessage(msg);
            },
        );

        this.resultsConsumer.consume(this.resultsQueue.name, {})
            .then(() => {
                logger.info(
                    `Worker[type='amqprpc'] consuming ${this.resultsQueue.name}`,
                    { node_id: this.settings.node_label.id},
                );
            });
    }

    /**
     * Accepts message, returns unsatisfied promise, which should be satisfied later
     *
     * @param {JobMessage} msg
     * @return {Promise<JobMessage[]>}
     */
    public processData(msg: JobMessage): Promise<JobMessage[]> {
        msg.getMeasurement().markWorkerStart();

        const uuid = uuid4();
        const headersToSend = new Headers(msg.getHeaders().getRaw());
        headersToSend.setPFHeader(Headers.NODE_ID, this.settings.node_label.node_id);
        headersToSend.setPFHeader(Headers.NODE_NAME, this.settings.node_label.node_name);

        this.publisher.sendToQueue(
            this.settings.publish_queue.name,
            new Buffer(msg.getContent()),
            {
                type: AmqpRpcWorker.BATCH_REQUEST_TYPE,
                replyTo: this.resultsQueue.name,
                correlationId: uuid,
                headers: headersToSend.getRaw(),
            },
        ).then(() => {
            logger.info(
                `Worker[type='amqprpc'] sent request to "${this.settings.publish_queue.name}" queue.`,
                logger.ctxFromMsg(msg),
            );
        }).catch((err: Error) => {
            logger.error(
                `Worker[type='amqprpc'] sending request to "${this.settings.publish_queue.name}" failed`,
                logger.ctxFromMsg(msg, err),
            );
        });

        if (this.waiting.has(uuid)) {
            this.onDuplicateMessage(msg);

            return Promise.resolve([msg]);
        }

        // resolve will be done with the last received result message
        return new Promise((resolve) => {
            // Set the original message not to be re-sent itself via classic Drain
            msg.setForwardSelf(false);
            msg.setMultiplier(0);

            const w: IWaiting = { resolveFn: resolve, message: msg, sequence: 0 };
            this.waiting.set(uuid, w);
        });
    }

    /**
     * Checks whether the worker counter-side is ready by sending test message and expecting result
     * @return {Promise<boolean>}
     */
    public isWorkerReady(): Promise<boolean> {
        const testCorrelationId = uuid4();

        return new Promise((resolve) => {
            const resolveTestFn = (msgs: JobMessage[]) => {

                if (!msgs[0]) {
                    return resolve(false);
                }

                const msg: JobMessage = msgs[0];
                if (msg.getCorrelationId() === AmqpRpcWorker.TEST_ID && msg.getResult().code === ResultCode.SUCCESS) {
                    resolve(true);
                } else {
                    resolve(false);
                }
            };

            const testHeaders = new Headers();
            testHeaders.setPFHeader(Headers.CORRELATION_ID, AmqpRpcWorker.TEST_ID);
            testHeaders.setPFHeader(Headers.PROCESS_ID, AmqpRpcWorker.TEST_ID);
            testHeaders.setPFHeader(Headers.PARENT_ID, "");
            testHeaders.setPFHeader(Headers.SEQUENCE_ID, "1");

            const jobMsg = new JobMessage(this.settings.node_label, testHeaders.getRaw(), new Buffer(""));
            const t: IWaiting = { resolveFn: resolveTestFn, message: jobMsg, sequence: 0 };
            this.waiting.set(testCorrelationId, t);

            this.publisher.sendToQueue(
                this.settings.publish_queue.name,
                new Buffer("Is worker ready test message."),
                {
                    type: AmqpRpcWorker.TEST_TYPE,
                    correlationId: testCorrelationId,
                    replyTo: this.resultsQueue.name,
                    headers: jobMsg.getHeaders().getRaw(),
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
                `Worker[type='amqprpc'] received result with unknown correlationId`,
                { node_id: this.settings.node_label.id, correlation_id: corrId },
            );

            return;
        }

        switch (msg.properties.type) {
            case AmqpRpcWorker.BATCH_ITEM_TYPE:
                this.updateWaiting(corrId, msg);
                break;
            case AmqpRpcWorker.BATCH_END_TYPE:
                this.resolveWaiting(corrId, msg);
                break;
            case AmqpRpcWorker.TEST_TYPE:
                this.resolveWaiting(corrId, msg);
                break;
            default:
                logger.warn(
                    `Worker[type='amqprpc'] received result of unknown type: ${msg.properties.type}`,
                    { node_id: this.settings.node_label.id, correlation_id: corrId },
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
        stored.message.setMultiplier(stored.message.getMultiplier() + 1);

        try {
            const splitMsg = new JobMessage(this.settings.node_label, resultMsg.properties.headers, resultMsg.content);

            splitMsg.getMeasurement().setPublished(stored.message.getMeasurement().getPublished());
            splitMsg.getMeasurement().setReceived(stored.message.getMeasurement().getReceived());
            splitMsg.getMeasurement().setWorkerStart(stored.message.getMeasurement().getWorkerStart());

            splitMsg.setResult({
                code: parseInt(splitMsg.getHeaders().getPFHeader(Headers.RESULT_CODE), 10),
                message: splitMsg.getHeaders().getPFHeader(Headers.RESULT_MESSAGE),
            });

            this.partialForwarder.forwardPart(splitMsg)
                .catch(() => {
                    logger.warn(`Worker[type='amqprpc'] partial forward failed.`, logger.ctxFromMsg(splitMsg));
                });

        } catch (err) {
            logger.error(`Worker[type='amqprpc'] partial message is invalid. Error: ${err}`);
        }
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

        const resultHeaders = new Headers(msg.properties.headers);

        let resultCode = ResultCode.MISSING_RESULT_CODE;
        const claimedCode = parseInt(resultHeaders.getPFHeader(Headers.RESULT_CODE), 10);
        if (claimedCode in ResultCode) {
            resultCode = claimedCode;
        }

        const resultMessage = resultHeaders.getPFHeader(Headers.RESULT_MESSAGE) ?
            resultHeaders.getPFHeader(Headers.RESULT_MESSAGE) : "";

        stored.message.setResult({ code: resultCode, message: resultMessage });

        // Resolves waiting promise
        stored.resolveFn([stored.message]);

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
            message: `Message[correlation_id=${msg.getCorrelationId()}] is already being processed.`,
        });
    }

}

export default AmqpRpcWorker;
