import {TimeUtils} from "hb-utils/dist/lib/TimeUtils";
import {INodeLabel} from "../topology/Configurator";
import AMessage from "./AMessage";
import Headers from "./Headers";
import IMessage from "./IMessage";
import {ResultCode, ResultCodeGroup} from "./ResultCode";

export interface IResult {
    code: ResultCode;
    message: string;
}

/**
 * Class representing the flowing message through the node
 */
class JobMessage extends AMessage implements IMessage {

    // timestamps
    private publishedTime: number;
    private receivedTime: number;
    private processedTime: number;
    private forwardedTime: number;

    private multiplier: number;
    private forwardSelf: boolean;

    /**
     *
     * @param {INodeLabel} node
     * @param {{}} headers
     * @param {Buffer} body
     * @param {IResult} result
     */
    constructor(
        node: INodeLabel,
        headers: { [key: string]: string },
        body: Buffer,
        private result?: IResult,
    ) {
        super(node, headers, body);

        this.receivedTime = TimeUtils.nowMili();;
        this.multiplier = 1;
        this.forwardSelf = true;

        this.headers.removeHeader(Headers.RESULT_CODE);
        this.headers.removeHeader(Headers.RESULT_MESSAGE);
    }

    /**
     *
     * @return {IResult}
     */
    public getResult(): IResult {
        if (!this.result) {
            return {
                code: ResultCode.MESSAGE_NOT_PROCESSED,
                message: "Message should have been modified by worker.",
            };
        }

        return this.result;
    }

    /**
     * Returns the first char of ResultCode that should equal to one of ResultCodeGroup
     *
     * @return {ResultCodeGroup}
     */
    public getResultGroup(): ResultCodeGroup {
        return parseInt(`${this.getResult().code}`.charAt(0), 10);
    }

    /**
     *
     * @param {IResult} result
     */
    public setResult(result: IResult): void {
        this.processedTime = TimeUtils.nowMili();
        this.result = result;
    }

    /**
     *
     * @param {number} count
     */
    public setMultiplier(count: number): void {
        this.multiplier = count;
    }

    /**
     *
     * @return {number}
     */
    public getMultiplier(): number {
        return this.multiplier;
    }

    /**
     *
     * @param {boolean} forward
     */
    public setForwardSelf(forward: boolean) {
        this.forwardSelf = forward;
    }

    /**
     *
     * @return {boolean}
     */
    public getForwardSelf(): boolean {
        return this.forwardSelf;
    }

    /**
     * Sets the timestamp when messgae was originally published in previous node
     *
     * @param {number} timestamp
     */
    public setPublishedTime(timestamp: number) {
        if (!timestamp || timestamp < 0) {
            timestamp = 0;
        }

        this.publishedTime = timestamp;
    }

    /**
     * Marks the message as published
     */
    public setForwardedTime(): void {
        this.forwardedTime = TimeUtils.nowMili();
    }

    /**
     * Returns the duration how long message was in broker,
     * between it's publishing in previous node and accepting in this node [ms]
     *
     * @return {number}
     */
    public getWaitingTime(): number {
        if (!this.publishedTime) {
            return 0;
        }

        return this.receivedTime - this.publishedTime;
    }

    /**
     * Returns in [ms] the time needed to process message
     *
     * @return {number}
     */
    public getProcessDuration(): number {
        if (this.processedTime && this.receivedTime) {
            return this.processedTime - this.receivedTime;
        }

        return 0;
    }

    /**
     * Returns in [ms] the time needed to process and publish message
     *
     * @return {number}
     */
    public getTotalDuration(): number {
        if (this.forwardedTime && this.receivedTime) {
            return this.forwardedTime - this.receivedTime;
        }

        return 0;
    }

}

export default JobMessage;
