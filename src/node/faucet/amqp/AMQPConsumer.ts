import { Channel, Message } from "amqplib";
import Connection from "lib-nodejs/dist/src/rabbitmq/Connection";
import { default as BasicConsumer } from "lib-nodejs/dist/src/rabbitmq/Consumer";
import logger from "../../../logger/Logger";
import JobMessage from "../../../message/JobMessage";
import { WorkerProcessFn } from "../../worker/IWorker";
import {FaucetProcessMsgFn} from "../IFaucet";

class Consumer extends BasicConsumer {

    private nodeId: string;
    private processData: WorkerProcessFn;

    constructor(
        nodeId: string,
        conn: Connection,
        channelCb: (ch: Channel) => Promise<any>,
        processData: FaucetProcessMsgFn,
    ) {
        super(conn, channelCb);
        this.nodeId = nodeId;
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
            logger.error(`AmqpFaucet dead-lettering message`, {node_id: this.nodeId, error: e});
            channel.nack(amqMsg, false, false); // dead-letter due to invalid message
            return;
        }

        this.processData(inMsg)
            .then(() => {
                channel.ack(amqMsg);
            })
            .catch((error: Error) => {
                logger.error(`AmqpFaucet requeue message`, {node_id: this.nodeId, error});
                channel.nack(amqMsg); // requeue due to processing error
            });
    }

}

export default Consumer;
