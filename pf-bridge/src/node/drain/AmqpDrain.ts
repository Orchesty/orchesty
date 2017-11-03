import {AssertionPublisher} from "amqplib-plus/dist/lib/AssertPublisher";
import {IMetrics} from "metrics-sender/dist/lib/metrics/IMetrics";
import logger from "../../logger/Logger";
import Headers from "../../message/Headers";
import JobMessage from "../../message/JobMessage";
import {ResultCode, ResultCodeGroup} from "../../message/ResultCode";
import {INodeLabel} from "../../topology/Configurator";
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
}

/**
 * Drain is responsible for passing messages to following node and for informing counter
 */
class AmqpDrain implements IDrain, IPartialForwarder {

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

        if (message.getResultGroup() === ResultCodeGroup.NON_STANDARD) {
            this.forwardNonStandard(message);
            return;
        }

        if (message.getResult().code === ResultCode.SUCCESS) {
            this.forwardSuccessMessage(message);
            return;
        }

        // On any error
        this.forwardToCounterOnly(message);
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

        if (!targetQueue) {
            message.setResult({code: ResultCode.REPEAT_INVALID_QUEUE, message: "Invalid repeat queue name"});
            return this.forward(message);
        }

        if (!headers.hasPFHeader(Headers.REPEAT_INTERVAL)) {
            message.setResult({
                code: ResultCode.REPEAT_INVALID_INTERVAL,
                message: `Missing "${Headers.REPEAT_INTERVAL}" header.`,
            });
            return this.forward(message);
        }

        if (!headers.hasPFHeader(Headers.REPEAT_HOPS) || !headers.hasPFHeader(Headers.REPEAT_MAX_HOPS)) {
            message.setResult({
                code: ResultCode.REPEAT_INVALID_HOPS,
                message: `Missing or invalid repeat hops headers. Headers: ${JSON.stringify(headers.getRaw())}`,
            });
            return this.forward(message);
        }

        const actualHops = parseInt(headers.getPFHeader(Headers.REPEAT_HOPS), 10);
        const maxHops = parseInt(headers.getPFHeader(Headers.REPEAT_MAX_HOPS), 10);

        if (actualHops > maxHops) {
            message.setResult({
                code: ResultCode.REPEAT_MAX_HOPS_REACHED,
                message: `Max repeat hops "${maxHops}" reached.`,
            });
            return this.forward(message);
        }

        // Send message to repeater
        // TODO - forward message properties
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
        message.setMultiplier(0);
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
