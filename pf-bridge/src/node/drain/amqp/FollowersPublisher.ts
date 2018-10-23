import {Channel, Options} from "amqplib";
import {Connection} from "amqplib-plus/dist/lib/Connection";
import {Publisher} from "amqplib-plus/dist/lib/Publisher";
import * as uuid4 from "uuid/v4";
import logger from "../../../logger/Logger";
import Headers from "../../../message/Headers";
import JobMessage from "../../../message/JobMessage";
import {IAmqpDrainSettings, IFollower} from "../AmqpDrain";

/**
 * This class will be injected to all drains and all counter result messages will be published using it
 */
class FollowersPublisher extends Publisher {

    private settings: IAmqpDrainSettings;

    /**
     *
     * @param {Connection} conn
     * @param {IAmqpDrainSettings} settings
     */
    constructor(conn: Connection, settings: IAmqpDrainSettings) {
        super(
            conn, (ch: Channel) => {
                // Prepare exchange to publish to and queue and bind for following node
                // in order not to loose messages if following node is not ready yet
                const followersPromises: any[] = [];

                settings.followers.forEach((f: IFollower) => {
                    const prom = ch.assertExchange(f.exchange.name, f.exchange.type, f.exchange.options)
                        .then(() => {
                            return ch.assertQueue(f.queue.name, f.queue.options);
                        })
                        .then(() => {
                            return ch.bindQueue(f.queue.name, f.exchange.name, f.routing_key);
                        });

                    followersPromises.push(prom);
                });

                return Promise.all(followersPromises)
                    .then(() => {
                        logger.debug("AmqpDrain followers publisher ready", {node_id: this.settings.node_label.id});
                    });
            });
        this.settings = settings;
    }

    /**
     *
     * @param {JobMessage} message
     * @return {Promise<void>}
     */
    public send(message: JobMessage): Promise<void> {
        const sent = this.sendToAllFollowers(message);

        return Promise.all(sent)
            .then(() => {
                logger.debug(
                    `AmqpDrain forwarded ${sent.length}x message. Followers: ${this.settings.followers.length}`,
                    logger.ctxFromMsg(message),
                );
            });
    }

    /**
     * Send message to all followers
     *
     * @param {JobMessage} message
     * @return {Array<Promise<void>>}
     */
    private sendToAllFollowers(message: JobMessage): Array<Promise<void>> {
        if (!message.getForwardSelf()) {
            return [Promise.resolve()];
        }

        const originalContentType = message.getHeaders().getHeader(Headers.CONTENT_TYPE);
        message.getHeaders().removeHeader(Headers.CONTENT_TYPE);

        const splitIntoChildProcesses = this.settings.followers.length > 1;
        const processId = message.getHeaders().getPFHeader(Headers.PROCESS_ID);

        const promises: Array<Promise<void>> = [];

        for (const follower of this.settings.followers) {
            const newHeaders = new Headers(message.getHeaders().getRaw());

            if (splitIntoChildProcesses) {
                newHeaders.setPFHeader(Headers.PARENT_ID, processId);
                newHeaders.setPFHeader(Headers.PROCESS_ID, uuid4());
            }

            const options: Options.Publish = {
                headers: newHeaders.getRaw(),
                contentType: originalContentType,
                type: "job_message",
                timestamp: Date.now(),
                appId: this.settings.node_label.id,
                persistent: this.settings.persistent,
            };

            const prom = this.publish(
                follower.exchange.name,
                follower.routing_key,
                new Buffer(message.getContent()),
                options,
            ).then(() => {
                logger.debug(
                    `Forwarded message. E: "${follower.exchange.name}", RK: "${follower.routing_key}"
                        Headers: ${JSON.stringify(options.headers)}`,
                    logger.ctxFromMsg(message),
                );
            });

            promises.push(prom);
        }

        return promises;
    }

}

export default FollowersPublisher;
