import {INodeLabel} from "../topology/Configurator";
import AMessage, {MessageType} from "./AMessage";
import Headers from "./Headers";
import IMessage from "./IMessage";
import {IRequest, IResponse} from "./JobMessage";
import {ResultCode} from "./ResultCode";

interface ICounterMessageContent {
    result: { code: ResultCode, originalCode: ResultCode, message: string, request?: IRequest, response?: IResponse; };
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
        private originalResultCode: ResultCode,
        private request?: IRequest,
        private response?: IResponse,
    ) {
        super(node, headers, Buffer.from(""));

        if (!this.headers.hasPFHeader(Headers.TOPOLOGY_ID)) {
            throw new Error(`Cannot create Counter message object. Missing topology-id header.`);
        }

        this.resultCode = resultCode;
        this.resultMsg = resultMsg;
        this.following = following;
        this.multiplier = multiplier;
    }

    /**
     * Returns the message type e.g. process|service
     * @return {string}
     */
    public getType(): string {
        return MessageType.COUNTER;
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
     */
    public getRequest(): IRequest | undefined {
        return this.request;
    }

    /**
     *
     */
    public getResponse(): IResponse | undefined {
        return this.response;
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
     *
     * @return {ResultCode}
     */
    public getOriginalResultCode(): ResultCode {
        return this.originalResultCode;
    }

    /**
     * @return {string}
     */
    public getContent(): string {
        const content: ICounterMessageContent = {
            result: {
                code: this.resultCode,
                originalCode: this.originalResultCode,
                message: this.resultMsg,
                request: this.request,
                response: this.response,
            },
            route: {
                following: this.following,
                multiplier: this.multiplier,
            },
        };

        const contentString = JSON.stringify(content);
        this.body = Buffer.from(contentString);

        return contentString;
    }

    /**
     *
     * @return {string}
     */
    public getTopologyId(): string {
        return this.headers.getPFHeader(Headers.TOPOLOGY_ID);
    }

    public isOk(): boolean {
        return [
            ResultCode.SUCCESS,
            ResultCode.REPEAT,
            ResultCode.FORWARD_TO_TARGET_QUEUE,
            ResultCode.DO_NOT_CONTINUE,
            ResultCode.LIMIT_EXCEEDED, // TODO: Is this success state?
            ResultCode.SPLITTER_BATCH_END,
        ].includes(this.getResultCode());
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

    public isFromStartingPoint(): boolean {
        return this.headers.hasPFHeader(Headers.FROM_STARTING_POINT) &&
            this.headers.getHeader(Headers.FROM_STARTING_POINT) === "1";
    }

}

export default CounterMessage;
