import { Channel, Message } from "amqplib";
import {Connection} from "amqplib-plus/dist/lib/Connection";
import { Consumer as BasicConsumer } from "amqplib-plus/dist/lib/Consumer";
import logger from "../../../logger/Logger";
import JobMessage from "../../../message/JobMessage";
import {INodeLabel} from "../../../topology/Configurator";
import {FaucetProcessMsgFn} from "../IFaucet";

class Consumer extends BasicConsumer {

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
        logger.info(`AmqpFaucet received message. \
            Headers: ${JSON.stringify(amqMsg.properties)}, Body: ${amqMsg.content.toString()}`);

        let inMsg: JobMessage;
        try {
            inMsg = new JobMessage(this.node, amqMsg.properties.headers, amqMsg.content);
            inMsg.getMeasurement().markReceived();
            inMsg.getMeasurement().setPublished(amqMsg.properties.timestamp);
            inMsg.getHeaders().setHeader("content-type", amqMsg.properties.contentType);
        } catch (e) {
            logger.error(`AmqpFaucet dead-lettering message`, {node_id: this.node.id, error: e});
            channel.nack(amqMsg, false, false); // dead-letter due to invalid message
            return;
        }

        this.processData(inMsg)
            .then(() => {
                try {
                    channel.ack(amqMsg);
                    logger.info("AmqpFaucet message ack");
                } catch (ackErr) {
                    logger.error(`Could not ack message. Error: ${ackErr}`);
                }
            })
            .catch((error: Error) => {
                try {
                    logger.error(`AmqpFaucet requeue message`, logger.ctxFromMsg(inMsg, error));
                    channel.nack(amqMsg); // requeue due to processing error
                } catch (ackErr) {
                    logger.error(`Could not nack message. Error: ${ackErr}`);
                }
            });
    }

}

export default Consumer;
