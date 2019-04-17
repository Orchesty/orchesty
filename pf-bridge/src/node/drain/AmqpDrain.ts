import {AssertionPublisher} from "amqplib-plus/dist/lib/AssertPublisher";
import {IMetrics} from "metrics-sender/dist/lib/metrics/IMetrics";
import logger from "../../logger/Logger";
import {MessageType} from "../../message/AMessage";
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
    persistent: boolean;
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

const MAX_REPEAT_IMMEDIATELY_LIMIT = 200;

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

        if (message.getType() === MessageType.PROCESS) {
            this.forwardProcessMessage(message);
            return;
        }

        if (message.getType() === MessageType.SERVICE) {
            this.forwardServiceMessage(message);
            return;
        }

        logger.error(`Drain cannot forward unknown message type : "${message.getType()}"`, logger.ctxFromMsg(message));
    }

    /**
     * Allows caller to forward single split messages transparently as he wishes
     * Does not send result to counter
     *
     * @param {JobMessage} message
     * @return {Promise<boolean>}
     */
    public async forwardPart(message: JobMessage): Promise<void> {
        await this.followersPublisher.send(message);
    }

    /**
     *
     * @param {JobMessage} message
     * @return {Promise<void>}
     */
    private forwardProcessMessage(message: JobMessage): Promise<void> {
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
                this.forwardToCounterOnly(message, 0);
                break;

            case ResultCode.STOP_AND_FAILED:
                message.setResult({
                    code: ResultCode.STOP_AND_FAILED,
                    message: `Process was terminated with code '${message.getResult().code}'`,
                });
                this.forwardToCounterOnly(message, 0);
                break;

            case ResultCode.SPLITTER_BATCH_END:
                // do nothing, final counter message already sent by splitter
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
        const headers = message.getHeaders();

        if (!headers.hasPFHeader(Headers.REPEAT_HOPS) ||
            !headers.hasPFHeader(Headers.REPEAT_MAX_HOPS)
        ) {
            message.setResult({
                code: ResultCode.REPEAT_INVALID_HOPS,
                message: "Forward Repeat Error. Missing or invalid repeat hops headers.}",
            });
            return this.forward(message);
        }

        const actualHops = parseInt(headers.getPFHeader(Headers.REPEAT_HOPS), 10);
        const maxHops = parseInt(headers.getPFHeader(Headers.REPEAT_MAX_HOPS), 10);

        if (actualHops > maxHops) {
            message.setResult({
                code: ResultCode.REPEAT_MAX_HOPS_REACHED,
                message: `Forward Repeat Error. Max repeat hops "${maxHops}" reached.`,
            });
            return this.forward(message);
        }

        if (!headers.hasPFHeader(Headers.REPEAT_INTERVAL)) {
            message.setResult({
                code: ResultCode.REPEAT_INVALID_INTERVAL,
                message: `Forward Repeat Error. Missing "${Headers.REPEAT_INTERVAL}" header.`,
            });
            return this.forward(message);
        }

        const interval = parseInt(headers.getPFHeader(Headers.REPEAT_INTERVAL), 10);
        if (interval < MAX_REPEAT_IMMEDIATELY_LIMIT) {
            // Repeat immediately by sending to node's input queue
            message.getHeaders().setPFHeader(Headers.FORCE_TARGET_QUEUE, this.settings.faucet.queue.name);
            return this.forwardToTargetQueue(message);
        }

        // Send to repeater microservice
        return this.forwardToRepeater(message);
    }

    /**
     * Sends message to repeater microservice
     *
     * @param {JobMessage} message
     */
    private forwardToRepeater(message: JobMessage): void {
        const repeaterQ: string = this.settings.repeater.queue.name;

        if (!repeaterQ) {
            message.setResult({
                code: ResultCode.REPEAT_INVALID_QUEUE,
                message: "Forward to Repeater error. Invalid repeater queue name",
            });
            return this.forward(message);
        }

        // Set the queue name where to repeat the message and send it to repeater
        message.getHeaders().setPFHeader(Headers.REPEAT_QUEUE, this.settings.faucet.queue.name);
        this.nonStandardPublisher.sendToQueue(repeaterQ, message.getBody(), {
            headers: message.getHeaders().getRaw(),
            persistent: this.settings.persistent,
        });
    }

    /**
     *
     * @param {JobMessage} message
     */
    private forwardToTargetQueue(message: JobMessage): void {
        const q: string = message.getHeaders().getPFHeader(Headers.FORCE_TARGET_QUEUE);

        if (!q) {
            message.setResult({
                code: ResultCode.INVALID_NON_STANDARD_TARGET_QUEUE,
                message: `Forward to target queue error. Missing or invalid target_queue header '${q}'`,
            });
            return this.forward(message);
        }

        this.nonStandardPublisher.sendToQueue(q, message.getBody(), {
            headers: message.getHeaders().getRaw(),
            persistent: this.settings.persistent,
        });
    }

    /**
     *
     * @param {JobMessage} message
     * @param {number} followforceFollowersCountersCount
     */
    private async forwardToCounterOnly(message: JobMessage, forceFollowersCount: number = null): Promise<void> {
        try {
            message.setMultiplier(0);
            message.getMeasurement().markFinished();
            await this.counterPublisher.send(message, forceFollowersCount);
        } catch (e) {
            logger.error("AmqpDrain could not send result message to counter", logger.ctxFromMsg(message, e));
        }
    }

    /**
     * Informs counter and send to all node's publishers
     *
     * @param {JobMessage} message
     */
    private async forwardSuccessMessage(message: JobMessage): Promise<void> {
        try {
            message.getMeasurement().markFinished();
            const followersCount = await this.followersPublisher.send(message);
            await this.counterPublisher.send(message, followersCount);
        } catch (e) {
            logger.error("AmqpDrain could not forward message", logger.ctxFromMsg(message, e));
        }
    }

    /**
     *
     * @param {JobMessage} message
     * @return {Promise<void>}
     */
    private async forwardServiceMessage(message: JobMessage): Promise<void> {
        try {
            message.getMeasurement().markFinished();
            await this.followersPublisher.send(message);
        } catch (e) {
            logger.error("AmqpDrain could not forward service  message", logger.ctxFromMsg(message, e));
        }
    }

}

export default AmqpDrain;
