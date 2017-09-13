import { Channel, Message } from "amqplib";
import logger from "lib-nodejs/dist/src/logger/Logger";
import Connection from "lib-nodejs/dist/src/rabbitmq/Connection";
import { default as BasicConsumer } from "lib-nodejs/dist/src/rabbitmq/Consumer";
import JobMessage from "../../../message/JobMessage";
import { WorkerProcessFn } from "../../worker/IWorker";
import {FaucetProcessMsgFn} from "../IFaucet";

class Consumer extends BasicConsumer {

    private processData: WorkerProcessFn;

    constructor(
        conn: Connection,
        channelCb: (ch: Channel) => Promise<any>,
        processData: FaucetProcessMsgFn,
    ) {
        super(conn, channelCb);
        this.processData = processData;
    }

    public processMessage(amqMsg: Message, channel: Channel): void {
        let inMsg: JobMessage;
        try {
            inMsg = new JobMessage(
                amqMsg.properties.headers.job_id,
                amqMsg.properties.headers.sequence_id,
                amqMsg.properties.headers,
                amqMsg.content.toString(),
            );
        } catch (e) {
            logger.error(`Dead-lettering message. Reason: ${e}`);
            channel.nack(amqMsg, false, false); // dead-letter due to invalid message
            return;
        }

        this.processData(inMsg)
            .then(() => {
                channel.ack(amqMsg);
            })
            .catch((error: Error) => {
                logger.error(`Requeue message. Reason: ${error}`);
                channel.nack(amqMsg); // requeue due to processing error
            });
    }

}

export default Consumer;
