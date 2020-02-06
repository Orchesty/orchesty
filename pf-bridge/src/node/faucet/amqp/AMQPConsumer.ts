import { Channel, Message } from "amqplib";
import {Connection, Consumer as BasicConsumer} from "amqplib-plus";
import logger from "../../../logger/Logger";
import {MessageType} from "../../../message/AMessage";
import Headers from "../../../message/Headers";
import JobMessage from "../../../message/JobMessage";
import {INodeLabel} from "../../../topology/Configurator";
import {FaucetProcessMsgFn} from "../IFaucet";

const NACK_TIMEOUT = 5000;

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

    /**
     *
     * @param {INodeLabel} node
     * @param {Connection} conn
     * @param {(ch: Channel) => Promise<any>} channelCb
     * @param {FaucetProcessMsgFn} processData
     */
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

    /**
     * Creates message object from consumed amqp message and passes it to processData function
     *
     * @param {Message} amqMsg
     * @param {Channel} channel
     */
    public processMessage(amqMsg: Message, channel: Channel): void {
        let inMsg: JobMessage;

        try {
            inMsg = this.createJobMessage(amqMsg);
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
                    setTimeout(() => {
                        channel.nack(amqMsg); // requeue due to processing error with delay
                    }, NACK_TIMEOUT);
                } catch (ackErr) {
                    logger.error(`Could not nack message. Error: ${ackErr}`, logger.ctxFromMsg(inMsg));
                }
            });
    }

    private createJobMessage(amqMsg: Message): JobMessage {
        const msg = new JobMessage(
            this.node,
            amqMsg.properties.headers,
            amqMsg.content,
            this.getMessageType(amqMsg),
        );

        msg.getMeasurement().markReceived();
        msg.getMeasurement().setPublished(Consumer.getPublishedTimestamp(msg, amqMsg));
        msg.getHeaders().setHeader("content-type", amqMsg.properties.contentType);

        return msg;
    }

    /**
     *
     * @param {Message} amqMsg
     * @return {MessageType}
     */
    private getMessageType(amqMsg: Message): MessageType {
        if (amqMsg.properties.type === MessageType.SERVICE) {
            return MessageType.SERVICE;
        }

        return MessageType.PROCESS;
    }

}

export default Consumer;
