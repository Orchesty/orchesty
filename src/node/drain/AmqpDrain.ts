import logger from "../../logger/Logger";
import JobMessage from "../../message/JobMessage";
import ADrain from "./ADrain";
import CounterPublisher from "./amqp/CounterPublisher";
import FollowersPublisher from "./amqp/FollowersPublisher";
import IDrain from "./IDrain";
import IPartialForwarder from "./IPartialForwarder";

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

export interface IAmqpDrainSettings {
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
class AmqpDrain extends ADrain implements IDrain, IPartialForwarder {

    /**
     *
     * @param {IAmqpDrainSettings} settings
     * @param {CounterPublisher} counterPublisher
     * @param {FollowersPublisher} followersPublisher
     */
    constructor(
        private settings: IAmqpDrainSettings,
        private counterPublisher: CounterPublisher,
        private followersPublisher: FollowersPublisher,
    ) {
        super(settings.node_id, settings.resequencer);
        this.settings = settings;
    }

    /**
     *
     * Forwards all buffered messages including their split messages if they have them to following node
     * and sends counter message with result
     *
     * @param {JobMessage} message
     */
    public forward(message: JobMessage): Promise<JobMessage> {
        return new Promise((resolve) => {
            const buffered = this.getMessageBuffer(message);
            buffered.forEach((bufMsg: JobMessage) => {
                this.counterPublisher.send(bufMsg)
                    .then(() => {
                        return this.followersPublisher.send(bufMsg);
                    })
                    .then(() => {
                        bufMsg.setPublishedTime();
                        resolve(bufMsg);
                    })
                    .catch((err: Error) => {
                        logger.error(
                            "AmqpDrain could not forward message",
                            { node_id: this.settings.node_id, correlation_id: message.getJobId(), error: err },
                        );

                        resolve(bufMsg);
                    });
            });
        });
    }

    /**
     * Allows caller to forward single split messages transparently as he wishes
     * Does not send result to counter
     *
     * @param {JobMessage} message
     * @return {Promise<boolean>}
     */
    public forwardSingleSplit(message: JobMessage): Promise<void> {
        return this.followersPublisher.send(message);
    }

}

export default AmqpDrain;
