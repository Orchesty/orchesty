import {Channel, Options} from "amqplib";
import Connection from "lib-nodejs/dist/src/rabbitmq/Connection";
import Publisher from "lib-nodejs/dist/src/rabbitmq/Publisher";
import logger from "../../../logger/Logger";
import JobMessage from "../../../message/JobMessage";
import {ResultCode} from "../../../message/ResultCode";
import {IAmqpDrainSettings, IFollower} from "../AmqpDrain";

/**
 * This class will be injected to all drains and all counter result messages will be published using it
 */
class FollowersPublisher extends Publisher {

    private settings: IAmqpDrainSettings;

    /**
     *
     * @param {AMQPConnection} conn
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
                        logger.info("AmqpDrain followers publisher ready", {node_id: this.settings.node_id});
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
        if (message.getResult().status !== ResultCode.SUCCESS) {
            logger.warn(
                `AmqpDrain will not forward message[", \
                status="${message.getResult().status}, message="${message.getResult().message}"].`,
                {node_id: this.settings.node_id, correlation_id: message.getJobId()},
            );

            return Promise.resolve();
        }

        const sendPromises = this.sendAll(message);

        return Promise.all(sendPromises)
            .then(() => {
                logger.info(
                    `AmqpDrain forwarded ${sendPromises.length}x message.\
                    Followers: ${this.settings.followers.length}, Split ${message.getSplit().length}.`,
                    { node_id: this.settings.node_id, correlation_id: message.getJobId() },
                );
            });
    }

    /**
     * Send message to all followers
     *
     * @param {JobMessage} message
     * @return {Array<Promise<void>>}
     */
    private sendAll(message: JobMessage): Array<Promise<void>> {
        const promises: Array<Promise<void>> = [];

        // Original message could have been split into partial messages
        for (const splitMsg of message.getSplit()) {
            const options: Options.Publish = {
                headers: splitMsg.getHeaders(),
                type: "job_message",
                messageId: splitMsg.getUuid(),
                timestamp: Date.now(),
                appId: this.settings.node_id,
            };

            for (const follower of this.settings.followers) {
                promises.push(
                    this.publish(
                        follower.exchange.name,
                        follower.routing_key,
                        new Buffer(splitMsg.getContent()),
                        options,
                    ),
                );
            }
        }

        return promises;
    }

}

export default FollowersPublisher;
