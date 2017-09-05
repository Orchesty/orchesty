import logger from "lib-nodejs/dist/src/logger/Logger";
import JobMessage from "../../message/JobMessage";
import ADrain from "./ADrain";
import CounterPublisher from "./amqp/CounterPublisher";
import FollowersPublisher from "./amqp/FollowersPublisher";
import IDrain from "./IDrain";

export interface IFollower {
    node_id: string;
    exchange: {
        name: string,
        type: string,
        options: any,
    };
    queue: {
        name: string,
        options: any,
    };
    routing_key: string;
}

export interface IAMQPDrainSettings {
    node_id: string;
    counter_event: {
        queue: {
            name: string,
            options: any,
        },
    };
    followers: IFollower[];
    resequencer: boolean;
}

/**
 * Drain is responsible for passing messages to following node and for informing counter
 */
class AMQPDrain extends ADrain implements IDrain {

    /**
     *
     * @param {IAMQPDrainSettings} settings
     * @param {CounterPublisher} counterPublisher
     * @param {FollowersPublisher} followersPublisher
     */
    constructor(
        private settings: IAMQPDrainSettings,
        private counterPublisher: CounterPublisher,
        private followersPublisher: FollowersPublisher,
    ) {
        super(settings.resequencer);
        this.settings = settings;
    }

    /**
     *
     * @param {JobMessage} message
     */
    public open(message: JobMessage): Promise<boolean> {
        return new Promise((resolve) => {
            this.getMessageBuffer(message).forEach((bufMsg: JobMessage) => {
                this.counterPublisher.send(bufMsg)
                    .then(() => {
                        return this.followersPublisher.send(bufMsg);
                    })
                    .then(() => {
                        logger.info("Drain forward complete.");

                        resolve(true);
                    })
                    .catch((err: Error) => {
                        logger.error(`Drain open error: ${err.message}`);

                        resolve(false);
                    });
            });
        });
    }

}

export default AMQPDrain;
