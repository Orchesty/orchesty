import TimeUtils from "lib-nodejs/dist/src/utils/TimeUtils";
import AMessage from "./AMessage";
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
    private receivedTime: number;
    private processedTime: number;
    private publishedTime: number;

    private multiplier: number;
    private forwardSelf: boolean;

    /**
     *
     * @param {string} nodeId
     * @param {string} correlationId
     * @param {string} processId
     * @param {number} sequenceId
     * @param {Object} headers
     * @param {string} parentId
     * @param {Buffer} body
     * @param {IResult} result
     *
     */
    constructor(
        nodeId: string,
        correlationId: string,
        processId: string,
        parentId: string,
        sequenceId: number,
        headers: { [key: string]: string },
        body: Buffer,
        private result?: IResult,
    ) {
        super(nodeId, correlationId, processId, parentId, sequenceId, headers, body);

        this.receivedTime = TimeUtils.nowMili();
        this.multiplier = 1;
        this.forwardSelf = true;

        // do not include following headers when calling getHeaders() if present
        delete headers.result_code;
        delete headers.result_message;
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
     * Marks the message as published
     */
    public setPublishedTime(): void {
        this.publishedTime = TimeUtils.nowMili();
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
        if (this.publishedTime && this.receivedTime) {
            return this.publishedTime - this.receivedTime;
        }

        return 0;
    }

    /**
     *
     * @return {number}
     */
    public getRepeatCount(): number {
        if (this.headers.repeat_count) {
            return parseInt(this.headers.repeat_count, 10);
        }

        return 0;
    }

}

export default JobMessage;
