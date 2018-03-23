import {INodeLabel} from "../topology/Configurator";
import AMessage from "./AMessage";
import Headers from "./Headers";
import IMessage from "./IMessage";
import {ResultCode, ResultCodeGroup} from "./ResultCode";

interface ICounterMessageContent {
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

        if (!this.headers.hasPFHeader(Headers.TOPOLOGY_ID)) {
            throw new Error(`Cannot create Counter message object. Missing topology-id header.`);
        }

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
     * Returns the first char of ResultCode that should equal to one of ResultCodeGroup
     *
     * @return {ResultCodeGroup}
     */
    public getResultGroup(): ResultCodeGroup {
        return parseInt(`${this.resultCode}`.charAt(0), 10);
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

    /**
     *
     * @return {string}
     */
    public getTopologyId(): string {
        return this.headers.getPFHeader(Headers.TOPOLOGY_ID);
    }

    /**
     *
     * @return {string}
     */
    public toString(): string {
        return JSON.stringify({
            processId: this.getProcessId(),
            resultCode: this.resultCode,
            following: this.following,
            multiplier: this.multiplier,
        });
    }

}

export default CounterMessage;
