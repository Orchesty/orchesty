import {Channel, Options} from "amqplib";
import Connection from "lib-nodejs/dist/src/rabbitmq/Connection";
import Publisher from "lib-nodejs/dist/src/rabbitmq/Publisher";
import CounterMessage from "../../../message/CounterMessage";
import JobMessage from "../../../message/JobMessage";
import {IAmqpDrainSettings} from "../AmqpDrain";

/**
 * This class will be injected to all drains and all counter result messages will be published using it
 */
class CounterPublisher extends Publisher {

    private settings: IAmqpDrainSettings;

    /**
     *
     * @param {AMQPConnection} conn
     * @param {IAmqpDrainSettings} settings
     */
    constructor(conn: Connection, settings: IAmqpDrainSettings) {
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
            this.settings.node_id,
            message.getCorrelationId(),
            message.getProcessId(),
            message.getParentId(),
            message.getResult().code, // 0 OK, >0 NOK
            message.getResult().message,
            this.settings.followers.length,
            message.getMultiplier(),
        );

        const opts: Options.Publish = {
            headers: resMsg.getHeaders(),
            type: "counter_message",
            timestamp: Date.now(),
            appId: this.settings.node_id,
        };

        return this.sendToQueue(
            this.settings.counter_event.queue.name,
            new Buffer(resMsg.getContent()),
            opts,
        );
    }

}

export default CounterPublisher;
