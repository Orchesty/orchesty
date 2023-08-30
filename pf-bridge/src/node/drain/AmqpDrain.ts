import { AssertionPublisher } from "amqplib-plus";
import { IMetrics } from "metrics-sender/dist/lib/metrics/IMetrics";
import logger from "../../logger/Logger";
import { MessageType } from "../../message/AMessage";
import Headers from "../../message/Headers";
import JobMessage from "../../message/JobMessage";
import { ResultCode, ResultCodeGroup } from "../../message/ResultCode";
import { INodeLabel } from "../../topology/Configurator";
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
    public async forward(message: JobMessage): Promise<void> {
        message.getHeaders().setHeader(Headers.PF_HEADERS_PREFIX + Headers.PUBLISHED_TIMESTAMP, Date.now().toString());
        if (message.getType() === MessageType.PROCESS) {
            await this.forwardProcessMessage(message);
            return;
        }

        if (message.getType() === MessageType.SERVICE) {
            await this.forwardServiceMessage(message);
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
    private async forwardProcessMessage(message: JobMessage): Promise<void> {
        if (message.getResultGroup() === ResultCodeGroup.NON_STANDARD) {
            await this.forwardNonStandard(message);
            return;
        }

        if (message.getResult().code === ResultCode.SUCCESS) {
            await this.forwardSuccessMessage(message);
            return;
        }

        // On any error
        await this.forwardToCounterOnly(message);
    }

    /**
     * Handles non-standard messages
     *
     * @param {JobMessage} message
     */
    private async forwardNonStandard(message: JobMessage): Promise<void> {
        let msg = '';
        switch (message.getResult().code) {

            // Handle non-standard result codes
            case ResultCode.REPEAT:
                await this.forwardRepeat(message);
                break;

            case ResultCode.FORWARD_TO_TARGET_QUEUE:
                await this.forwardToTargetQueue(message);
                break;

            case ResultCode.DO_NOT_CONTINUE:
                await this.forwardToCounterOnly(message, 0);
                break;

            case ResultCode.STOP_AND_FAILED:
                msg = `Process was terminated with code '${message.getResult().code}'`;
                if (message.getHeaders().hasPFHeader(Headers.RESULT_MESSAGE)) {
                    msg = message.getHeaders().getPFHeader(Headers.RESULT_MESSAGE);
                }

                message.setResult({
                    code: ResultCode.STOP_AND_FAILED,
                    message: msg,
                });
                await this.forwardToCounterOnly(message, 0);
                break;

            case ResultCode.SPLITTER_BATCH_END:
                // do nothing, final counter message already sent by splitter
                break;

            default:
                msg = `Unknown non-standard result code '${message.getResult().code}'`;
                if (message.getHeaders().hasPFHeader(Headers.RESULT_MESSAGE)) {
                    msg = message.getHeaders().getPFHeader(Headers.RESULT_MESSAGE);
                }

                // Let the message fail
                message.setResult({
                    code: ResultCode.INVALID_NON_STANDARD_CODE,
                    message: msg,
                });
                await this.forward(message);
        }
    }

    /**
     *
     * @param {JobMessage} message
     */
    private async forwardRepeat(message: JobMessage): Promise<void> {
        const headers = message.getHeaders();

        if (!headers.hasPFHeader(Headers.REPEAT_HOPS) ||
            !headers.hasPFHeader(Headers.REPEAT_MAX_HOPS)
        ) {
            message.setResult({
                code: ResultCode.REPEAT_INVALID_HOPS,
                message: "Forward Repeat Error. Missing or invalid repeat hops headers.}",
            });
            return await this.forward(message);
        }

        const actualHops = parseInt(headers.getPFHeader(Headers.REPEAT_HOPS), 10);
        const maxHops = parseInt(headers.getPFHeader(Headers.REPEAT_MAX_HOPS), 10);

        if (actualHops > maxHops) {
            message.setResult({
                code: ResultCode.REPEAT_MAX_HOPS_REACHED,
                message: `Forward Repeat Error. Max repeat hops "${maxHops}" reached.`,
            });
            return await this.forward(message);
        }

        if (!headers.hasPFHeader(Headers.REPEAT_INTERVAL)) {
            message.setResult({
                code: ResultCode.REPEAT_INVALID_INTERVAL,
                message: `Forward Repeat Error. Missing "${Headers.REPEAT_INTERVAL}" header.`,
            });
            return await this.forward(message);
        }

        const interval = parseInt(headers.getPFHeader(Headers.REPEAT_INTERVAL), 10);
        if (interval < MAX_REPEAT_IMMEDIATELY_LIMIT) {
            // Repeat immediately by sending to node's input queue
            message.getHeaders().setPFHeader(Headers.FORCE_TARGET_QUEUE, this.settings.faucet.queue.name);
            return await this.forwardToTargetQueue(message);
        }

        // Send to repeater microservice
        return await this.forwardToRepeater(message);
    }

    /**
     * Sends message to repeater microservice
     *
     * @param {JobMessage} message
     */
    private async forwardToRepeater(message: JobMessage): Promise<void> {
        const repeaterQ: string = this.settings.repeater.queue.name;

        if (!repeaterQ) {
            message.setResult({
                code: ResultCode.REPEAT_INVALID_QUEUE,
                message: "Forward to Repeater error. Invalid repeater queue name",
            });
            return await this.forward(message);
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
    private async forwardToTargetQueue(message: JobMessage): Promise<void> {
        let q: string = message.getHeaders().getPFHeader(Headers.FORCE_TARGET_QUEUE);

        if (!q) {
            message.setResult({
                code: ResultCode.INVALID_NON_STANDARD_TARGET_QUEUE,
                message: `Forward to target queue error. Missing or invalid target_queue header '${q}'`,
            });
            return await this.forward(message);
        }

        // For some unknown reason, force-target-queue contains wrong topology_id (which is taken from header pf-topology-id)
        const topology_id = this.settings.node_label.topology_id;
        const parts = q.split('.')
        parts[1] = topology_id
        q = parts.join('.')
        const headers = message.getHeaders();
        headers.removePFHeader(Headers.FORCE_TARGET_QUEUE)

        await this.nonStandardPublisher.sendToQueue(q, message.getBody(), {
            headers: headers.getRaw(),
            persistent: this.settings.persistent,
        });
    }

    /**
     *
     * @param {JobMessage} message
     * @param {number} forceFollowersCount
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