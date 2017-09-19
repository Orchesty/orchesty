import * as uuid from "uuid/v1";
import IMessage from "./IMessage";
import { ResultCode } from "./ResultCode";

export interface ICounterMessageHeaders {
    job_id: string;
    node_id: string;
}

export interface ICounterMessageContent {
    result: { code: ResultCode, message: string };
    route: { following: number, multiplier: number };
}

class CounterMessage implements IMessage {

    private jobId: string;
    private nodeId: string;
    private resultCode: number;
    private resultMsg: string;
    private following: number;
    private multiplier: number;
    private msgUuid: string;

    /**
     *
     * @param {string} jobId
     * @param {string} nodeId
     * @param {number} resultCode
     * @param {string} resultMsg
     * @param {number} following
     * @param {number} multiplier
     */
    constructor(
        jobId: string,
        nodeId: string,
        resultCode = ResultCode.SUCCESS,
        resultMsg = "",
        following = 0,
        multiplier = 1,
    ) {
        this.jobId = jobId;
        this.nodeId = nodeId;
        this.resultCode = resultCode;
        this.resultMsg = resultMsg;
        this.following = following;
        this.multiplier = multiplier;
        this.msgUuid = uuid();
    }

    /**
     *
     * @return CounterMessageHeaders
     */
    public getHeaders(): ICounterMessageHeaders {
        return {
            job_id: this.jobId,
            node_id: this.nodeId,
        };
    }

    /**
     * @return {string}
     */
    public getContent(): string {
        const content: ICounterMessageContent = {
            result: {
                code: this.resultCode,
                message: this.resultMsg,
            },
            route: {
                following: this.following,
                multiplier: this.multiplier,
            },
        };

        return JSON.stringify(content);
    }

    /**
     *
     * @return {string}
     */
    public getUuid(): string {
        return this.msgUuid;
    }

}

export default CounterMessage;
