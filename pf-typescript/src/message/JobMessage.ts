import TimeUtils from "lib-nodejs/dist/src/utils/TimeUtils";
import * as uuid from "uuid/v1";
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

    private msgUuid: string;

    // timestamps
    private receivedTime: number;
    private processedTime: number;
    private publishedTime: number;

    /**
     *
     * @param {string} jobId
     * @param {number} sequenceId
     * @param {Object} headers
     * @param {string} content
     * @param result
     */
    constructor(
        private jobId: string,
        private sequenceId: number,
        private headers: { [key: string]: string },
        private content: string,
        private result?: IResult,
    ) {
        if (!jobId) {
            throw new Error("Invalid jobId.");
        }
        if (!sequenceId) {
            throw new Error("Invalid sequenceId.");
        }

        this.receivedTime = TimeUtils.nowMili();
        this.headers = headers;
        this.addHeader("job_id", jobId);
        this.addHeader("sequence_id", `${sequenceId}`);
        this.content = content;

        this.msgUuid = `${jobId}-${sequenceId}-${uuid()}`;
    }

    /**
     *
     * @return {string}
     */
    public getUuid(): string {
        return this.msgUuid;
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
        return this.headers;
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
     * @param key
     * @param value
     */
    public addHeader(key: string, value: string): void {
        this.headers[key] = value;
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
