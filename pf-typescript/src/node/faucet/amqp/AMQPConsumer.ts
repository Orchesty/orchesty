import { Channel, Message } from "amqplib";
import logger from "lib-nodejs/dist/src/logger/Logger";
import Connection from "lib-nodejs/dist/src/rabbitmq/Connection";
import { default as BasicConsumer } from "lib-nodejs/dist/src/rabbitmq/Consumer";
import JobMessage from "../../../message/JobMessage";
import { WorkerProcessFn } from "../../worker/IWorker";

class Consumer extends BasicConsumer {

    private processData: WorkerProcessFn;
    private drain: (outMsg: JobMessage) => {};

    constructor(
        conn: Connection,
        channelCb: (ch: Channel) => Promise<any>,
        processData: WorkerProcessFn,
        drain: (outMsg: JobMessage) => {},
    ) {
        super(conn, channelCb);
        this.processData = processData;
        this.drain = drain;
    }

    public processMessage(amqMsg: Message, channel: Channel): void {
        let message: JobMessage;
        try {
            message = new JobMessage(
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

        this.processData(message)
            .then((outMsg: JobMessage) => {
                channel.ack(amqMsg);
                return this.drain(outMsg);
            })
            .catch((error: Error) => {
                logger.error(`Requeuing message. Reason: ${error.message}`);
                channel.nack(amqMsg); // requeue due to worker processing error
            });
    }

}

export default Consumer;
