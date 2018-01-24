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
import IWorker from "./IWorker";

export interface IAmqpWorkerSettings {
    node_label: INodeLabel;
    publish_queue: {
        name: string;
        options: any;
    };
}

export interface IWaiting {
    resolveFn: any;
    message: JobMessage;
    sequence: number;
}

/**
 * TODO add waiting timeout
 */
abstract class AAmqpWorker implements IWorker {

    public static readonly TEST_TYPE = "test";
    public static readonly TEST_ID = "pipes.worker.amqprpc.test";

    public static readonly BATCH_REQUEST_TYPE = "batch";
    public static readonly BATCH_END_TYPE = "batch_end";
    public static readonly BATCH_ITEM_TYPE = "batch_item";

    protected waiting: Container;

    private publisher: Publisher;
    private resultsQueue: { name: string, options: any, prefetch: number };
    private resultsConsumer: SimpleConsumer;

    /**
     *
     * @param {Connection} connection
     * @param {IAmqpWorkerSettings} settings
     */
    constructor(
        protected connection: Connection,
        protected settings: IAmqpWorkerSettings,
    ) {
        this.waiting = new Container();
        this.resultsQueue = {
            name: `pipes.${settings.node_label.topology_id}.${settings.node_label.id}_reply`,
            options: settings.publish_queue.options || { durable: false, exclusive: false, autoDelete: false },
            prefetch: 1,
        };

        const publisherPrepare = async (ch: Channel): Promise<void> => {
            const q = settings.publish_queue;
            await ch.assertQueue(q.name, q.options || { durable: false, exclusive: false, autoDelete: false });
        };

        const resultsConsumerPrepare = async (ch: Channel): Promise<void> => {
            await ch.assertQueue(this.resultsQueue.name, this.resultsQueue.options);
            await ch.prefetch(this.resultsQueue.prefetch);
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
     * Handle single incoming response message as you wish
     * You can store it and wait for batch_end, forward it without waiting for batch end etc. as you wish
     *
     * @param {string} corrId
     * @param {AmqpMessage} resultMsg
     */
    public abstract onBatchItem(corrId: string, resultMsg: AmqpMessage): Promise<void>;

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
                type: AAmqpWorker.BATCH_REQUEST_TYPE,
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
                if (msg.getCorrelationId() === AAmqpWorker.TEST_ID &&
                    msg.getResult().code === ResultCode.SUCCESS
                ) {
                    logger.info(`Worker[type'amqp'] worker ready.`, {node_id: this.settings.node_label.node_id});
                    resolve(true);
                } else {
                    logger.warn(`Worker[type'amqp'] worker not ready.`, {node_id: this.settings.node_label.node_id});
                    resolve(false);
                }
            };

            const testHeaders = new Headers();
            testHeaders.setPFHeader(Headers.CORRELATION_ID, AAmqpWorker.TEST_ID);
            testHeaders.setPFHeader(Headers.PROCESS_ID, AAmqpWorker.TEST_ID);
            testHeaders.setPFHeader(Headers.PARENT_ID, "");
            testHeaders.setPFHeader(Headers.SEQUENCE_ID, "1");

            const jobMsg = new JobMessage(this.settings.node_label, testHeaders.getRaw(), new Buffer(""));
            const t: IWaiting = { resolveFn: resolveTestFn, message: jobMsg, sequence: 0 };
            this.waiting.set(testCorrelationId, t);

            logger.info(
                `Worker[type'amqp'] asking worker if is ready via queue ${this.settings.publish_queue.name}`,
                {node_id: this.settings.node_label.node_id},
            );

            this.publisher.sendToQueue(
                this.settings.publish_queue.name,
                new Buffer("Is worker ready test message."),
                {
                    type: AAmqpWorker.TEST_TYPE,
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
            case AAmqpWorker.BATCH_ITEM_TYPE:
                this.onBatchItem(corrId, msg);
                break;
            case AAmqpWorker.BATCH_END_TYPE:
                this.onBatchEnd(corrId, msg);
                break;
            case AAmqpWorker.TEST_TYPE:
                this.onBatchEnd(corrId, msg);
                break;
            default:
                logger.warn(
                    `Worker[type='amqprpc'] received result of unknown type: ${msg.properties.type}`,
                    { node_id: this.settings.node_label.id, correlation_id: corrId },
                );
        }
    }

    /**
     * Resolves the stored promise with populated message
     * @param {string} corrId
     * @param {AmqpMessage} msg
     */
    private onBatchEnd(corrId: string, msg: AmqpMessage): void {
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

export default AAmqpWorker;
