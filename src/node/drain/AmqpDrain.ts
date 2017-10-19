import IMetrics from "lib-nodejs/dist/src/metrics/IMetrics";
import AssertionPublisher from "lib-nodejs/dist/src/rabbitmq/AssertPublisher";
import logger from "../../logger/Logger";
import Headers from "../../message/Headers";
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
     * @param {IMetrics} metrics
     */
    constructor(
        private settings: IAmqpDrainSettings,
        private counterPublisher: CounterPublisher,
        private followersPublisher: FollowersPublisher,
        private nonStandardPublisher: AssertionPublisher,
        private metrics: IMetrics,
    ) {
        super(settings.node_label.id, settings.resequencer);
        this.settings = settings;
    }

    /**
     *
     * Forward given message and all preceding messages of this message (by sequenceId) to following nodes
     * Also send counter message for each message
     *
     *
     * @param {JobMessage} message
     */
    public forward(message: JobMessage): void {

        const bufferedMessages = this.getMessageBuffer(message);

        bufferedMessages.forEach((bufMsg: JobMessage) => {
            if (bufMsg.getResultGroup() === ResultCodeGroup.NON_STANDARD) {
                this.forwardNonStandard(bufMsg);
                return;
            }

            if (bufMsg.getResult().code === ResultCode.SUCCESS) {
                this.forwardSuccessMessage(bufMsg);
                return;
            }

            // On any error
            this.forwardToCounterOnly(bufMsg);
        });
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
     */
    private forwardNonStandard(message: JobMessage): void {
        switch (message.getResult().code) {

            // Handle non-standard result codes
            case ResultCode.REPEAT:
                this.forwardRepeat(message);
                break;

            case ResultCode.FORWARD_TO_TARGET_QUEUE:
                this.forwardToTargetQueue(message);
                break;

            case ResultCode.DO_NOT_CONTINUE:
                this.forwardToCounterOnly(message);
                break;

            default:
                // Let the message fail
                message.setResult({
                    code: ResultCode.INVALID_NON_STANDARD_CODE,
                    message: `Unknown non-standard result code '${message.getResult().code}'`,
                });
                this.forward(message);
        }
    }

    /**
     *
     * @param {JobMessage} message
     */
    private forwardRepeat(message: JobMessage): void {
        const targetQueue: string = this.settings.repeater.queue.name;

        const headers = message.getHeaders();
        headers.setPFHeader(Headers.REPEAT_QUEUE, this.settings.faucet.queue.name);

        const props = { headers: message.getHeaders().getRaw() };

        this.nonStandardPublisher.sendToQueue(targetQueue, message.getBody(), props);
    }

    /**
     *
     * @param {JobMessage} message
     */
    private forwardToTargetQueue(message: JobMessage): void {
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
        this.nonStandardPublisher.sendToQueue(targetQueue, message.getBody(), {});
    }

    /**
     *
     * @param {JobMessage} message
     */
    private forwardToCounterOnly(message: JobMessage): void {
        this.counterPublisher.send(message)
            .then(() => {
                message.setPublishedTime();
                this.sendTotalDurationMetric(message);
            });
    }

    /**
     * Informs counter and send to all node's publishers
     *
     * @param {JobMessage} message
     */
    private forwardSuccessMessage(message: JobMessage): void {
        this.counterPublisher.send(message)
            .then(() => {
                return this.followersPublisher.send(message);
            })
            .then(() => {
                message.setPublishedTime();
                this.sendTotalDurationMetric(message);
            })
            .catch((err: Error) => {
                logger.error("AmqpDrain could not forward message", logger.ctxFromMsg(message, err));
            });
    }

    /**
     *
     * @param {JobMessage} msg
     */
    private sendTotalDurationMetric(msg: JobMessage): void {
        this.metrics.send({node_total_duration: msg.getTotalDuration()})
            .catch((err) => {
                logger.warn("Unable to send node metrics", logger.ctxFromMsg(msg, err));
            });
    }

}

export default AmqpDrain;
