import {Channel, Message as AmqpMessage} from "amqplib";
import {Connection, Publisher} from "amqplib-plus";
import {Container} from "hb-utils/dist/lib/Container";
import { v4 as uuid4 } from 'uuid';
import logger from "../../logger/Logger";
import Headers from "../../message/Headers";
import JobMessage from "../../message/JobMessage";
import {ResultCode} from "../../message/ResultCode";
import {INodeLabel} from "../../topology/Configurator";
import AWorker from "./AWorker";
import {SimpleConsumer} from "../../consumer/SimpleConsumer";

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
abstract class AAmqpWorker extends AWorker {

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
    protected constructor(
        protected connection: Connection,
        protected settings: IAmqpWorkerSettings,
    ) {
        super();

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
                logger.debug(
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
     * Handle the worker's confirmation message informing you about the batch end
     *
     * @param {string} corrId
     * @param {Message} msg
     */
    public abstract onBatchEnd(corrId: string, msg: AmqpMessage): void;

    /**
     * Accepts message, returns unsatisfied promise, which should be satisfied later
     *
     * @param {JobMessage} msg
     * @return {Promise<JobMessage[]>}
     */
    public async processData(msg: JobMessage): Promise<JobMessage[]> {
        const uuid = uuid4();
        const headersToSend = new Headers(msg.getHeaders().getRaw());
        headersToSend.setPFHeader(Headers.NODE_ID, this.settings.node_label.node_id);
        headersToSend.setPFHeader(Headers.NODE_NAME, this.settings.node_label.node_name);

        try {
            await this.publisher.sendToQueue(
                this.settings.publish_queue.name,
                Buffer.from(msg.getContent()),
                {
                    type: AAmqpWorker.BATCH_REQUEST_TYPE,
                    replyTo: this.resultsQueue.name,
                    correlationId: uuid,
                    headers: headersToSend.getRaw(),
                },
            );
            logger.debug(
                `Worker[type='amqprpc'] sent request to "${this.settings.publish_queue.name}" queue.`,
                logger.ctxFromMsg(msg),
            );
        } catch (err) {
            logger.error(
                `Worker[type='amqprpc'] sending request to "${this.settings.publish_queue.name}" failed`,
                logger.ctxFromMsg(msg, err),
            );
        }

        if (this.waiting.has(uuid)) {
            this.onDuplicateMessage(msg);

            return [msg];
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
        return new Promise((resolve) => {
            const resolveTestFn = (msgs: JobMessage[]) => {

                if (!msgs[0]) {
                    return resolve(false);
                }

                const msg: JobMessage = msgs[0];
                if (this.isWorkerReadyResponseSuccessful(msg)) {
                    logger.info(`Worker[type'amqp'] worker ready - OK.`, {node_id: this.settings.node_label.node_id});
                    resolve(true);
                } else {
                    logger.warn(
                        `Worker[type'amqp'] worker not ready. Result code=[${msg.getResult().code}]`,
                        {
                            node_id: this.settings.node_label.node_id,
                            data : JSON.stringify(msg.getResult()),
                        },
                    );
                    resolve(false);
                }
            };

            this.sendReadinessTestMessage(resolveTestFn);
        });
    }

    /**
     * Creates test message, stores it's resolve function and send message to rabbitmq
     *
     * @param resolveReadinessTestFn
     */
    private sendReadinessTestMessage(resolveReadinessTestFn: any) {
        const testCorrelationId = uuid4();

        const testHeaders = new Headers();
        testHeaders.setPFHeader(Headers.CORRELATION_ID, AAmqpWorker.TEST_ID);
        testHeaders.setPFHeader(Headers.PROCESS_ID, AAmqpWorker.TEST_ID);
        testHeaders.setPFHeader(Headers.PARENT_ID, "");
        testHeaders.setPFHeader(Headers.SEQUENCE_ID, "1");
        testHeaders.setPFHeader(Headers.TOPOLOGY_ID, AAmqpWorker.TEST_ID);

        const jobMsg = new JobMessage(this.settings.node_label, testHeaders.getRaw(), Buffer.from(""));
        const t: IWaiting = { resolveFn: resolveReadinessTestFn, message: jobMsg, sequence: 0 };
        this.waiting.set(testCorrelationId, t);

        logger.debug(
            `Worker[type'amqp'] asking worker if is ready via queue ${this.settings.publish_queue.name}`,
            {node_id: this.settings.node_label.node_id},
        );

        this.publisher.sendToQueue(
            this.settings.publish_queue.name,
            Buffer.from("Is worker ready test message."),
            {
                type: AAmqpWorker.TEST_TYPE,
                correlationId: testCorrelationId,
                replyTo: this.resultsQueue.name,
                headers: jobMsg.getHeaders().getRaw(),
            },
        );
    }

    /**
     * Returns true if worker's response means the worker is ready
     *
     * @param {JobMessage} msg
     * @return {boolean}
     */
    private isWorkerReadyResponseSuccessful(msg: JobMessage): boolean {
        if (msg.getCorrelationId() !== AAmqpWorker.TEST_ID) {
            return false;
        }

        if (msg.getResult().code === ResultCode.SUCCESS ||
            msg.getResult().code === ResultCode.SPLITTER_BATCH_END
        ) {
            return true;
        }

        return false;
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
