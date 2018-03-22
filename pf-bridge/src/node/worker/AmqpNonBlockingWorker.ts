import {Message as AmqpMessage} from "amqplib";
import {Connection} from "amqplib-plus/dist/lib/Connection";
import logger from "../../logger/Logger";
import Headers from "../../message/Headers";
import JobMessage from "../../message/JobMessage";
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
            stored.message.setForwardSelf(false);
            // stored.message.setMultiplier(stored.message.getMultiplier() + 1);

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
     * Forwards the message to following bridge
     *
     * @param {JobMessage} msg
     * @return {Promise<void>}
     */
    private async forwardBatchItem(msg: JobMessage): Promise<void> {
        try {
            logger.warn("SPLITTER WILL FORWARD", { data: JSON.stringify({
                    fwdSelf: msg.getForwardSelf(),
                    group: msg.getResultGroup(),
                    result: msg.getResult(),
                })});

            await this.counterPublisher.send(msg);
            await this.partialForwarder.forwardPart(msg);
        } catch (e) {
            logger.warn(`Worker[type='amqprpc'] partial forward failed.`, logger.ctxFromMsg(msg, e));
            return;
        }
    }

}

export default AmqpNonBlockingWorker;
