import { Channel } from "amqplib";
import logger from "lib-nodejs/dist/src/logger/Logger";
import Connection from "lib-nodejs/dist/src/rabbitmq/Connection";
import Publisher from "lib-nodejs/dist/src/rabbitmq/Publisher";
import JobMessage from "../../../message/JobMessage";
import {IAMQPDrainSettings, IFollower} from "../AMQPDrain";

/**
 * This class will be injected to all drains and all counter result messages will be published using it
 */
class FollowersPublisher extends Publisher {

    private settings: IAMQPDrainSettings;

    /**
     *
     * @param {AMQPConnection} conn
     * @param {IAMQPDrainSettings} settings
     */
    constructor(conn: Connection, settings: IAMQPDrainSettings) {
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
                        logger.info(`Followers exchanges, queues and binds ready.`);
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
        const options = { headers: message.getHeaders(), messageId: message.getId() };

        const promises: Array<Promise<void>> = [];
        this.settings.followers.forEach((follower: IFollower) => {
            promises.push(
                this.publish(
                    follower.exchange.name,
                    follower.routing_key,
                    new Buffer(message.getContent()),
                    options,
                ),
            );
        });

        return Promise.all(promises)
            .then(() => {
                logger.info(`Messages forwarded to ${promises.length} followers.`);
            });
    }

}

export default FollowersPublisher;
