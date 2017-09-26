import TimeUtils from "lib-nodejs/dist/src/utils/TimeUtils";
import IMessage from "./IMessage";
import { ResultCode } from "./ResultCode";

export interface IResult {
    status: ResultCode;
    message: string;
}

/**
 * Class representing the flowing message through the node
 */
class JobMessage implements IMessage {

    // timestamps
    private receivedTime: number;
    private processedTime: number;
    private publishedTime: number;

    private multiplier: number;
    private forwardSelf: boolean;

    /**
     *
     * @param correlationId
     * @param {string} jobId
     * @param {number} sequenceId
     * @param {Object} headers
     * @param {string} content
     * @param result
     *
     * AMQP Message Mandatory headers
     *  - correlation_id
     *  - process_id
     *  - sequenceId
     *
     */
    constructor(
        private correlationId: string,
        private jobId: string,
        private sequenceId: number,
        private headers: { [key: string]: string },
        private content: string,
        private result?: IResult,
    ) {
        if (!correlationId) {
            throw new Error("Invalid correlationId.");
        }
        if (!jobId) {
            throw new Error("Invalid jobId.");
        }
        if (!sequenceId) {
            throw new Error("Invalid sequenceId.");
        }

        this.receivedTime = TimeUtils.nowMili();

        delete headers.job_id;
        delete headers.sequence_id;
        delete headers.correlation_id;

        this.headers = headers;
        this.content = content;

        this.multiplier = 1;
        this.forwardSelf = true;
    }

    /**
     *
     * @return {string}
     */
    public getCorrelationId(): string {
        return this.correlationId;
    }

    /**
     *
     * @return {string}
     */
    public getJobId(): string {
        return this.jobId;
    }

    /**
     *
     * @return {Number}
     */
    public getSequenceId(): number {
        return this.sequenceId;
    }

    /**
     *
     * @param key
     * @return {*}
     */
    public getHeader(key: string): string {
        return this.headers[key];
    }

    /**
     *
     * @return {*}
     */
    public getHeaders(): { [key: string]: string } {
        const h = this.headers;
        h.job_id = this.getJobId();
        h.sequence_id = `${this.getSequenceId()}`;

        return h;
    }

    /**
     *
     * @return {string}
     */
    public getContent(): string {
        return this.content;
    }

    /**
     *
     * @param {string} content
     */
    public setContent(content: string) {
        this.content = content;
    }

    /**
     *
     * @return {IResult}
     */
    public getResult(): IResult {
        if (!this.result) {
            return {
                status: ResultCode.NOT_PROCESSED,
                message: "Message was not changed by any worker.",
            };
        }

        return this.result;
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

}

export default JobMessage;
