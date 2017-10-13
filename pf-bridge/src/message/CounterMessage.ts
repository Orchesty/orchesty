import {INodeLabel} from "../topology/Configurator";
import AMessage from "./AMessage";
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

    constructor(
        node: INodeLabel,
        headers: { [key: string]: string },
        private resultCode: ResultCode,
        private resultMsg: string = "",
        private following: number = 0,
        private multiplier: number = 1,
    ) {
        super(node, headers, new Buffer(""));

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
