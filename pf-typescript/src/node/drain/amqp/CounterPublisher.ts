import { Channel } from "amqplib";
import Connection from "lib-nodejs/dist/src/rabbitmq/Connection";
import Publisher from "lib-nodejs/dist/src/rabbitmq/Publisher";
import CounterMessage from "../../../message/CounterMessage";
import JobMessage from "../../../message/JobMessage";
import {IAMQPDrainSettings} from "../AMQPDrain";

/**
 * This class will be injected to all drains and all counter result messages will be published using it
 */
class CounterPublisher extends Publisher {

    private settings: IAMQPDrainSettings;

    /**
     *
     * @param {AMQPConnection} conn
     * @param {IAMQPDrainSettings} settings
     */
    constructor(conn: Connection, settings: IAMQPDrainSettings) {
        super(
            conn,
            (ch: Channel) => {
                const q = settings.counter_event.queue;

                return new Promise((resolve) => {
                    ch.assertQueue(q.name, q.options)
                        .then(() => {
                            resolve();
                        });
                });
            },
        );
        this.settings = settings;
    }

    /**
     * Sends the counter info message
     *
     * @param {JobMessage} message
     * @return {Promise<void>}
     */
    public send(message: JobMessage): Promise<void> {
        const resMsg = new CounterMessage(
            message.getJobId(),
            this.settings.node_id,
            message.getJobResultCode(), // 0 OK, >0 NOK
            message.getJobResultMessage(),
            this.settings.followers.length,
            1, // TODO - unhardcode 1 if the node is of type "splitter"
        );

        const opts = { headers: resMsg.getHeaders() };

        return this.sendToQueue(this.settings.counter_event.queue.name, new Buffer(resMsg.getContent()), opts);
    }

}

export default CounterPublisher;
