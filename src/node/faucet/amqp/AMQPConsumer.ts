import { Channel, Message } from "amqplib";
import Connection from "lib-nodejs/dist/src/rabbitmq/Connection";
import { default as BasicConsumer } from "lib-nodejs/dist/src/rabbitmq/Consumer";
import logger from "../../../logger/Logger";
import Headers from "../../../message/Headers";
import {CORRELATION_ID_HEADER, PARENT_ID_HEADER, PROCESS_ID_HEADER, SEQUENCE_ID_HEADER} from "../../../message/Headers";
import JobMessage from "../../../message/JobMessage";
import {INodeLabel} from "../../../topology/Configurator";
import { WorkerProcessFn } from "../../worker/IWorker";
import {FaucetProcessMsgFn} from "../IFaucet";

class Consumer extends BasicConsumer {

    private node: INodeLabel;
    private processData: WorkerProcessFn;

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
            // validate headers and remove all non pf-headers
            const headers = Headers.getPFHeaders(amqMsg.properties.headers);
            Headers.validateMandatoryHeaders(headers);

            inMsg = new JobMessage(this.node, new Headers(headers), amqMsg.content);
        } catch (e) {
            logger.error(`AmqpFaucet dead-lettering message`, {node_id: this.node.id, error: e});
            channel.nack(amqMsg, false, false); // dead-letter due to invalid message
            return;
        }

        this.processData(inMsg)
            .then(() => {
                channel.ack(amqMsg);
            })
            .catch((error: Error) => {
                logger.error(`AmqpFaucet requeue message`, logger.ctxFromMsg(inMsg, error));
                channel.nack(amqMsg); // requeue due to processing error
            });
    }

}

export default Consumer;
