import * as uuid from "uuid/v1";
import IMessage from "./IMessage";
import { ResultCode } from "./ResultCode";

class JobMessage implements IMessage {

    private headers: any;
    private content: string;
    private sequenceId: number;
    private msgId: string;

    /**
     *
     * @param headers {Object}
     * @param content {String}
     */
    constructor(headers: any, content: string) {
        if (!headers.job_id) {
            throw new Error("Cannot instantiate JobMessage. Missing job_id.");
        }
        if (!headers.sequence_id || parseInt(headers.sequence_id, 10) < 0) {
            throw new Error("Cannot instantiate JobMessage. Missing or invalid sequence_id");
        }

        this.headers = headers;
        this.content = content;
        this.sequenceId = parseInt(headers.sequence_id, 10);
        this.msgId = `${headers.job_id}-${uuid()}`;
    }

    /**
     *
     * @return {string}
     */
    public getId() {
        return this.msgId;
    }

    /**
     *
     * @return {string}
     */
    public getJobId() {
        return this.headers.job_id;
    }

    /**
     *
     * @return {Number}
     */
    public getSequenceId() {
        return this.sequenceId;
    }

    /**
     *
     * @param key
     * @param value
     */
    public setHeader(key: string, value: string | number) {
        this.headers[key] = value;
    }

    /**
     *
     * @param key
     * @return {*}
     */
    public getHeader(key: string) {
        if (this.headers[key] || this.headers[key] === 0) {
            return this.headers[key];
        }

        return null;
    }

    /**
     *
     * @return {*}
     */
    public getHeaders() {
        return this.headers;
    }

    /**
     *
     * @param content {String}
     */
    public setContent(content: string) {
        this.content = content;
    }

    /**
     *
     * @return {string}
     */
    public getContent() {
        return this.content;
    }

    /**
     *
     * @return { data, settings }
     */
    public open(): { data: any, settings: any } {
        const parsed = JSON.parse(this.content);

        if (!parsed.data) {
            throw new Error(`Opening message ${this.getId()}, but no data found inside.`);
        }
        if (!parsed.settings) {
            throw new Error(`Opening message ${this.getId()}, but no settings found inside.`);
        }

        return parsed;
    }

    /**
     * @param message
     */
    public setJobResultOK(message = "") {
        this.setHeader("result.code", ResultCode.SUCCESS);
        this.setHeader("result.message", message);
    }

    /**
     * @param errorCode
     * @param message
     */
    public setJobResultFailed(errorCode: number, message = "") {
        this.setHeader("result.code", errorCode);
        this.setHeader("result.message", message);
    }

    /**
     * Will return job status if is set, or 1 if not
     *
     * status === 0  => OK
     * status > 0  => NOK
     *
     * @return {int}
     */
    public getJobResultCode() {
        if (this.getHeader("result.code") !== null) {
            return this.getHeader("result.code");
        }

        return 1;
    }

    /**
     * @return {string}
     */
    public getJobResultMessage() {
        if (this.getHeader("result.message") !== null) {
            return this.getHeader("result.message");
        }

        return "";
    }

}

export default JobMessage;
