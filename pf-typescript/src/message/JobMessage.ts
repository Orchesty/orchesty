import * as uuid from "uuid/v1";
import IMessage from "./IMessage";
import { ResultCode } from "./ResultCode";

export interface IResult {
    status: ResultCode;
    message: string;
}

/**
 * Immutable class representing the flowing message through the node
 */
class JobMessage implements IMessage {

    private msgUuid: string;

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

        this.headers = headers;
        this.addHeader("job_id", jobId);
        this.addHeader("sequence_id", `${sequenceId}`);
        this.content = content;

        this.msgUuid = `${jobId}-${sequenceId}-${uuid()}`;

        if (!this.result) {
            this.result = {
                status: ResultCode.NOT_PROCESSED,
                message: "",
            };
        }
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
    public getHeaders(): {} {
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
     * @return {IResult}
     */
    public getResult(): IResult {
        return this.result;
    }

    /**
     *
     * @param key
     * @param value
     */
    private addHeader(key: string, value: string): void {
        this.headers[key] = value;
    }

}

export default JobMessage;
