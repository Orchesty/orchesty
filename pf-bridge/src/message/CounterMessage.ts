import IMessage from "./IMessage";
import { ResultCode } from "./ResultCode";

export interface ICounterMessageHeaders {
    node_id: string;
    correlation_id: string;
    process_id: string;
    parent_id: string;
}

export interface ICounterMessageContent {
    result: { code: ResultCode, message: string };
    route: { following: number, multiplier: number };
}

class CounterMessage implements IMessage {

    private nodeId: string;
    private correlationId: string;
    private processId: string;
    private parentId: string;
    private resultCode: number;
    private resultMsg: string;
    private following: number;
    private multiplier: number;

    /**
     *
     * @param {string} processId
     * @param {string} nodeId
     * @param {string} correlationId
     * @param {string} parentId
     * @param {number} resultCode
     * @param {string} resultMsg
     * @param {number} following
     * @param {number} multiplier
     */
    constructor(
        nodeId: string,
        correlationId: string,
        processId: string,
        parentId: string,
        resultCode: ResultCode,
        resultMsg: string = "",
        following: number = 0,
        multiplier: number = 1,
    ) {
        if (!nodeId || nodeId === "") {
            throw new Error(`Invalid counter message nodeId: ${nodeId}`);
        }
        if (!correlationId || correlationId === "") {
            throw new Error(`Invalid counter message correlationId: ${correlationId}`);
        }
        if (!processId || processId === "") {
            throw new Error(`Invalid counter message processId: ${processId}`);
        }

        this.nodeId = nodeId;
        this.correlationId = correlationId;
        this.processId = processId;
        this.parentId = parentId;
        this.resultCode = resultCode;
        this.resultMsg = resultMsg;
        this.following = following;
        this.multiplier = multiplier;
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
     *
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
     * @return {number}
     */
    public getFollowing(): number {
        return this.following;
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
     * @return CounterMessageHeaders
     */
    public getHeaders(): ICounterMessageHeaders {
        return {
            node_id: this.getNodeId(),
            correlation_id: this.getCorrelationId(),
            process_id: this.getProcessId(),
            parent_id: this.getParentId(),
        };
    }

    /**
     *
     * @return {string}
     */
    public getResultMsg(): string {
        return this.resultMsg;
    }

    /**
     *
     * @return {ResultCode}
     */
    public getResultCode(): ResultCode {
        return this.resultCode;
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

}

export default CounterMessage;
