import {INodeLabel} from "../topology/Configurator";
import AMessage from "./AMessage";
import {CORRELATION_ID_HEADER, PARENT_ID_HEADER, PROCESS_ID_HEADER, SEQUENCE_ID_HEADER} from "./Headers";
import IMessage from "./IMessage";
import { ResultCode } from "./ResultCode";

export interface ICounterMessageHeaders {
    node_id: string;
    node_name: string;
    correlation_id: string;
    process_id: string;
    parent_id: string;
    sequence_id: number;
}

export interface ICounterMessageContent {
    result: { code: ResultCode, message: string };
    route: { following: number, multiplier: number };
}

class CounterMessage extends AMessage implements IMessage {

    /**
     *
     * @param {INodeLabel} node
     * @param {string} correlationId
     * @param {string} processId
     * @param {string} parentId
     * @param {number} sequenceId
     * @param {ResultCode} resultCode
     * @param {string} resultMsg
     * @param {number} following
     * @param {number} multiplier
     */
    constructor(
        node: INodeLabel,
        correlationId: string,
        processId: string,
        parentId: string,
        sequenceId: number,
        private resultCode: ResultCode,
        private resultMsg: string = "",
        private following: number = 0,
        private multiplier: number = 1,
    ) {
        super(node, correlationId, processId, parentId, sequenceId, {}, new Buffer(""));

        this.resultCode = resultCode;
        this.resultMsg = resultMsg;
        this.following = following;
        this.multiplier = multiplier;
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
        const h = super.getHeaders();

        delete h[CORRELATION_ID_HEADER];
        delete h[PROCESS_ID_HEADER];
        delete h[PARENT_ID_HEADER];
        delete h[SEQUENCE_ID_HEADER];

        h.node_id = this.getNodeId();
        h.correlation_id = this.getCorrelationId();
        h.process_id = this.getProcessId();
        h.parent_id = this.getParentId();
        h.sequence_id = this.getSequenceId();

        return h;
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

        const contentString = JSON.stringify(content);
        this.body = new Buffer(contentString);

        return contentString;
    }

}

export default CounterMessage;
