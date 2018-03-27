import { Channel, Message } from "amqplib";
import {Connection} from "amqplib-plus/dist/lib/Connection";
import { Consumer as BasicConsumer } from "amqplib-plus/dist/lib/Consumer";
import logger from "../../../logger/Logger";
import Headers from "../../../message/Headers";
import JobMessage from "../../../message/JobMessage";
import {INodeLabel} from "../../../topology/Configurator";
import {FaucetProcessMsgFn} from "../IFaucet";

class Consumer extends BasicConsumer {

    /**
     * Returns the message published timestamp [ms]
     *
     * @param {JobMessage}jobMsg
     * @param {Message} amqMsg
     * @returns {number}
     */
    private static getPublishedTimestamp(jobMsg: JobMessage, amqMsg: Message): number {
        let published = amqMsg.properties.timestamp;
        if (jobMsg.getHeaders().hasPFHeader(Headers.PUBLISHED_TIMESTAMP)) {
            published = jobMsg.getHeaders().getPFHeader(Headers.PUBLISHED_TIMESTAMP);
        }

        return parseInt(published, 10);
    }

    private node: INodeLabel;
    private processData: FaucetProcessMsgFn;

    constructor(
        node: INodeLabel,
        conn: Connection,
        channelCb: (ch: Channel) => Promise<any>,
        processData: FaucetProcessMsgFn,
    ) {
        super(conn, channelCb);
        this.node = node;
        this.processData = processData;
    }

    public processMessage(amqMsg: Message, channel: Channel): void {
        let inMsg: JobMessage;
        try {
            inMsg = new JobMessage(this.node, amqMsg.properties.headers, amqMsg.content);
            inMsg.getMeasurement().markReceived();
            inMsg.getMeasurement().setPublished(Consumer.getPublishedTimestamp(inMsg, amqMsg));
            inMsg.getHeaders().setHeader("content-type", amqMsg.properties.contentType);

            logger.debug(`AmqpFaucet received message.`, logger.ctxFromMsg(inMsg));
        } catch (e) {
            logger.error(`AmqpFaucet dead-lettering message`, {node_id: this.node.id, error: e});
            channel.nack(amqMsg, false, false); // dead-letter due to invalid message
            return;
        }

        this.processData(inMsg)
            .then(() => {
                try {
                    channel.ack(amqMsg);
                    logger.debug("AmqpFaucet message ack", logger.ctxFromMsg(inMsg));
                } catch (ackErr) {
                    logger.error(`Could not ack message. Error: ${ackErr}`, logger.ctxFromMsg(inMsg));
                }
            })
            .catch((error: Error) => {
                try {
                    logger.error(`AmqpFaucet requeue message`, logger.ctxFromMsg(inMsg, error));
                    channel.nack(amqMsg); // requeue due to processing error
                } catch (ackErr) {
                    logger.error(`Could not nack message. Error: ${ackErr}`, logger.ctxFromMsg(inMsg));
                }
            });
    }

}

export default Consumer;
