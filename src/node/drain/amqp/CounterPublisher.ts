import {Channel, Options} from "amqplib";
import Connection from "amqplib-plus/dist/lib/Connection";
import Publisher from "amqplib-plus/dist/lib/Publisher";
import logger from "../../../logger/Logger";
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
                const q = settings.counter.queue;

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
        const counterMessage = new CounterMessage(
            this.settings.node_label,
            message.getHeaders().getRaw(),
            message.getResult().code, // 0 OK, >0 NOK
            message.getResult().message,
            this.settings.followers.length,
            message.getMultiplier(),
        );

        const opts: Options.Publish = {
            headers: counterMessage.getHeaders().getRaw(),
            type: "counter_message",
            timestamp: Date.now(),
            appId: this.settings.node_label.id,
        };

        logger.info(
            `Counter publisher - sending message. ${JSON.stringify(counterMessage)}`,
            logger.ctxFromMsg(message),
        );

        return this.sendToQueue(
            this.settings.counter.queue.name,
            new Buffer(counterMessage.getContent()),
            opts,
        );
    }

}

export default CounterPublisher;
