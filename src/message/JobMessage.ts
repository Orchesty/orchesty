import TimeUtils from "lib-nodejs/dist/src/utils/TimeUtils";
import IMessage from "./IMessage";
import { ResultCode } from "./ResultCode";

export interface IResult {
    code: ResultCode;
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
     * @param {string} nodeId
     * @param {string} correlationId
     * @param {string} processId
     * @param {number} sequenceId
     * @param {Object} headers
     * @param {string} content
     * @param {string} parentId
     * @param {IResult} result
     *
     * AMQP Message Mandatory headers
     *  - correlation_id
     *  - process_id
     *  - sequence_id
     *  - parent_id
     *
     */
    constructor(
        private nodeId: string,
        private correlationId: string,
        private processId: string,
        private parentId: string,
        private sequenceId: number,
        private headers: { [key: string]: string },
        private content: string,
        private result?: IResult,
    ) {
        if (!nodeId || nodeId === "") {
            throw new Error(`Invalid nodeId. "${nodeId}"`);
        }
        if (!correlationId || correlationId === "") {
            throw new Error(`Invalid correlationId. "${correlationId}"`);
        }
        if (!processId || processId === "") {
            throw new Error(`Invalid processId. "${processId}"`);
        }
        if (!sequenceId || sequenceId < 1) {
            throw new Error(`Invalid sequenceId. "${sequenceId}"`);
        }

        this.receivedTime = TimeUtils.nowMili();
        this.multiplier = 1;
        this.forwardSelf = true;

        this.setHeaders(headers);
        this.content = content;
    }

    /**
     *
     * @return {string}
     */
    public getNodeId(): string {
        return this.nodeId;
    }

    /**
     *
     * @return {string}
     */
    public getCorrelationId(): string {
        return this.correlationId;
    }

    /**
     * @return {string}
     */
    public getProcessId(): string {
        return this.processId;
    }

    /**
     *
     * @return {string}
     */
    public getParentId(): string {
        return this.parentId;
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
     * Returns custom headers amended by system headers
     *
     * @return {*}
     */
    public getHeaders(): { [key: string]: string } {
        const h = this.headers;

        h.correlation_id = this.getCorrelationId();
        h.process_id = this.getProcessId();
        h.parent_id = this.getParentId();
        h.sequence_id = `${this.getSequenceId()}`;

        return h;
    }

    /**
     * Cleans headers from system headers and set them to header field
     *
     * @param {{[p: string]: string}} headers
     */
    public setHeaders(headers: { [key: string]: string }): void {
        delete headers.correlation_id;
        delete headers.process_id;
        delete headers.parent_id;
        delete headers.sequence_id;

        delete headers.result_code;
        delete headers.result_message;

        this.headers = headers;
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
                code: ResultCode.MESSAGE_NOT_PROCESSED,
                message: "Message should have been modified by worker.",
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
