import {Channel, Options} from "amqplib";
import {Connection, Publisher} from "amqplib-plus";
import Headers from "../../message/Headers";
import JobMessage from "../../message/JobMessage";
import {ILimiterSettings} from "../Limiter";

/**
 * This class will be injected to all drains and all counter result messages will be published using it
 */
class LimiterPublisher extends Publisher {

    /**
     *
     * @param {Connection} conn
     * @param {ILimiterSettings} settings
     */
    constructor(conn: Connection, private settings: ILimiterSettings) {
        super(
            conn,
            (ch: Channel) => {
                return new Promise(async (resolve) => {
                    await ch.assertQueue(settings.queue.name, settings.queue.options);
                    resolve();
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
        const options: Options.Publish = {
            contentType: message.getHeaders().getPFHeader(Headers.CONTENT_TYPE) || "",
            headers: message.getHeaders().getRaw(),
            type: "job_message",
            timestamp: Date.now(),
        };

        return this.sendToQueue(this.settings.queue.name, Buffer.from(message.getContent()), options);
    }

}

export default LimiterPublisher;
