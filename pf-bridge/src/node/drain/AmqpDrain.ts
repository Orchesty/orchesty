import AssertionPublisher from "lib-nodejs/dist/src/rabbitmq/AssertPublisher";
import logger from "../../logger/Logger";
import JobMessage from "../../message/JobMessage";
import {ResultCode, ResultCodeGroup} from "../../message/ResultCode";
import {INodeLabel} from "../../topology/Configurator";
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
    node_label: INodeLabel;
    counter: {
        queue: {
            name: string,
            options: any,
        },
    };
    repeater: {
        queue: {
            name: string,
            options: any,
        },
    };
    faucet: {
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
     * @param {AssertionPublisher} nonStandardPublisher
     */
    constructor(
        private settings: IAmqpDrainSettings,
        private counterPublisher: CounterPublisher,
        private followersPublisher: FollowersPublisher,
        private nonStandardPublisher: AssertionPublisher,
    ) {
        super(settings.node_label.id, settings.resequencer);
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

        if (message.getResultGroup() === ResultCodeGroup.NON_STANDARD) {
            return this.forwardNonStandard(message);
        }

        return this.forwardStandardMessage(message);
    }

    /**
     * Allows caller to forward single split messages transparently as he wishes
     * Does not send result to counter
     *
     * @param {JobMessage} message
     * @return {Promise<boolean>}
     */
    public forwardPart(message: JobMessage): Promise<void> {
        return this.followersPublisher.send(message);
    }

    /**
     * Handles non-standard messages
     *
     * @param {JobMessage} message
     * @return {Promise<JobMessage>}
     */
    private forwardNonStandard(message: JobMessage): Promise<JobMessage> {
        switch (message.getResult().code) {

            // Handle non-standard result codes
            case ResultCode.REPEAT:
                return this.forwardRepeat(message);

            case ResultCode.FORWARD_TO_TARGET_QUEUE:
                return this.forwardToTargetQueue(message);

            default:
                // Let the message fail
                message.setResult({
                    code: ResultCode.INVALID_NON_STANDARD_CODE,
                    message: `Unknown non-standard result code '${message.getResult().code}'`,
                });
                return this.forward(message);
        }
    }

    /**
     *
     * @param {JobMessage} message
     * @return {Promise<JobMessage>}
     */
    private forwardRepeat(message: JobMessage): Promise<JobMessage> {
        const targetQueue: string = this.settings.repeater.queue.name;

        const headers = message.getHeaders();
        headers.setPFHeader("repeat_target_queue", this.settings.faucet.queue.name);

        const props = { headers: message.getHeaders().getRaw() };

        return this.nonStandardPublisher.sendToQueue(targetQueue, message.getBody(), props)
            .then(() => {
                return message;
            });
    }

    /**
     *
     * @param {JobMessage} message
     * @return {Promise<JobMessage>}
     */
    private forwardToTargetQueue(message: JobMessage): Promise<JobMessage> {
        const targetQueue: string = message.getHeaders().getRaw().target_queue;

        if (!targetQueue) {
            // Let the message fail
            message.setResult({
                code: ResultCode.INVALID_NON_STANDARD_TARGET_QUEUE,
                message: `Missing or invalid target_queue header '${targetQueue}'`,
            });
            return this.forward(message);
        }

        // TODO - prepare message options
        return this.nonStandardPublisher.sendToQueue(targetQueue, message.getBody(), {})
            .then(() => {
                return message;
            });
    }

    /**
     * Informs counter and send to all node's publishers
     *
     * @param {JobMessage} message
     * @return {Promise<JobMessage>}
     */
    private forwardStandardMessage(message: JobMessage): Promise<JobMessage> {
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
                        logger.error("AmqpDrain could not forward message", logger.ctxFromMsg(message, err));

                        resolve(bufMsg);
                    });
            });
        });
    }

}

export default AmqpDrain;
