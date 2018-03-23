import {Message as AmqpMessage} from "amqplib";
import {Connection} from "amqplib-plus/dist/lib/Connection";
import logger from "../../logger/Logger";
import Headers from "../../message/Headers";
import JobMessage, {IResult} from "../../message/JobMessage";
import {ResultCode} from "../../message/ResultCode";
import {ICounterPublisher} from "../drain/amqp/CounterPublisher";
import IPartialForwarder from "../drain/IPartialForwarder";
import AAmqpWorker, {IAmqpWorkerSettings, IWaiting} from "./AAmqpWorker";

/**
 * This Non-blocking worker forwards all incoming response messages immediately using partialForwarder when received
 * On received batch_end message it resolves the promise for input message.
 */
class AmqpNonBlockingWorker extends AAmqpWorker {

    /**
     *
     * @param {Connection} connection
     * @param {IAmqpWorkerSettings} settings
     * @param {IPartialForwarder} partialForwarder
     * @param {ICounterPublisher} counterPublisher
     */
    constructor(
        protected connection: Connection,
        protected settings: IAmqpWorkerSettings,
        private partialForwarder: IPartialForwarder,
        private counterPublisher: ICounterPublisher,
    ) {
        super(connection, settings);
    }

    // /**
    //  *
    //  * @param {JobMessage} msg
    //  * @return {Promise<JobMessage[]>}
    //  */
    // public async processData(msg: JobMessage): Promise<JobMessage[]> {
    //     await this.counterPublisher.send(this.createMessageCopy(msg), 1);
    //
    //     return super.processData(msg);
    // }

    /**
     * Updates the JobMessage object stored in memory
     *
     * @param {string} corrId
     * @param {AmqpMessage} resultMsg
     */
    public async onBatchItem(corrId: string, resultMsg: AmqpMessage): Promise<void> {
        try {
            const stored: IWaiting = this.waiting.get(corrId);
            stored.sequence++;

            const item = new JobMessage(this.settings.node_label, resultMsg.properties.headers, resultMsg.content);
            item.getMeasurement().copyValues(stored.message.getMeasurement());
            item.setResult({
                code: parseInt(item.getHeaders().getPFHeader(Headers.RESULT_CODE), 10),
                message: item.getHeaders().getPFHeader(Headers.RESULT_MESSAGE),
            });

            if (item.getResult().code !== ResultCode.SUCCESS) {
                logger.warn(`Worker[type='amqprpc'] received non-Success batch-item message`, logger.ctxFromMsg(item));
                return;
            }

            return await this.forwardBatchItem(item);

        } catch (err) {
            logger.error(`Worker[type='amqprpc'] cannot create partial message. Error: ${err}`);
        }
    }

    /**
     * Resolves the stored promise with populated message
     * @param {string} corrId
     * @param {AmqpMessage} msg
     */
    public async onBatchEnd(corrId: string, msg: AmqpMessage): Promise<void> {
        const stored: IWaiting = this.waiting.get(corrId);
        if (!stored) {
            logger.warn(`Worker[type='amqprpc'] cannot resolve non-existing waiting promise[corrId=${corrId}]`);
            return;
        }

        stored.message.setResult(this.getResultFromBatchEnd(msg));
        // await this.counterPublisher.send(stored.message, 0);
        stored.resolveFn([stored.message]); // Resolves waiting promise
        this.waiting.delete(corrId);
    }

    /**
     * Forwards the message to following bridge
     *
     * @param {JobMessage} msg
     * @return {Promise<void>}
     */
    private async forwardBatchItem(msg: JobMessage): Promise<void> {
        try {
            await this.counterPublisher.send(msg);
            await this.partialForwarder.forwardPart(msg);
        } catch (e) {
            logger.warn(`Worker[type='amqprpc'] partial forward failed.`, logger.ctxFromMsg(msg, e));
            return;
        }
    }

    /**
     *
     * @param {Message} batchEndMsg
     * @return {IResult}
     */
    private getResultFromBatchEnd(batchEndMsg: AmqpMessage): IResult {
        const resultHeaders = new Headers(batchEndMsg.properties.headers);

        let resultCode = ResultCode.MISSING_RESULT_CODE;

        // Check result code validity
        const claimedCode = parseInt(resultHeaders.getPFHeader(Headers.RESULT_CODE), 10);
        if (claimedCode in ResultCode) {
            resultCode = claimedCode;
        }

        // if result OK, change it to BATCH_END for proper process termination in counter
        if (resultCode === ResultCode.SUCCESS) {
            resultCode = ResultCode.SPLITTER_BATCH_END;
        }

        const resultMessage = resultHeaders.getPFHeader(Headers.RESULT_MESSAGE) ?
            resultHeaders.getPFHeader(Headers.RESULT_MESSAGE) : "";

        return { code: resultCode, message: resultMessage };
    }

    /**
     *
     * @param {JobMessage} msg
     * @return {JobMessage}
     */
    private createMessageCopy(msg: JobMessage): JobMessage {
        const cloned = new JobMessage(
            msg.getNodeLabel(),
            msg.getHeaders().getRaw(),
            new Buffer("splitter batch end"),
        );
        cloned.setResult({
            code: ResultCode.SUCCESS,
            message: "Amqp splitter OK",
        });

        return cloned;
    }

}

export default AmqpNonBlockingWorker;
