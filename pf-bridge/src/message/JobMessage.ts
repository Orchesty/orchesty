import {INodeLabel} from "../topology/Configurator";
import AMessage, {MessageType} from "./AMessage";
import Headers from "./Headers";
import IMessage from "./IMessage";
import {Measurement} from "./Measurement";
import {ResultCode, ResultCodeGroup} from "./ResultCode";

export interface IResult {
    code: ResultCode;
    message: string;
}

export interface IRequest {
    method: string;
    url: string;
    body: string;
    headers: any;
}

export interface IResponse {
    status: number;
    body: string;
    headers: any;
}

/**
 * Class representing the flowing message through the node
 */
class JobMessage extends AMessage implements IMessage {

    private type: string;
    private result: IResult;
    private measurement: Measurement;
    private multiplier: number;
    private forwardSelf: boolean;
    private request?: IRequest;
    private response?: IResponse;

    /**
     *
     * @param {INodeLabel} node
     * @param {{}} headers
     * @param {Buffer} body
     * @param {MessageType} type
     */
    constructor(
        node: INodeLabel,
        headers: { [key: string]: string },
        body: Buffer,
        type?: MessageType,
    ) {
        super(node, headers, body);

        this.type = type ? type : MessageType.PROCESS;
        this.measurement = new Measurement();
        this.multiplier = 1;
        this.forwardSelf = true;

        this.headers.removeHeader(Headers.RESULT_CODE);
        this.headers.removeHeader(Headers.RESULT_MESSAGE);
    }

    /**
     * Returns the message type e.g. process|service
     * @return {string}
     */
    public getType(): string {
        return this.type;
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
     * Returns the first char of ResultCode that should equal to one of ResultCodeGroup
     *
     * @return {ResultCodeGroup}
     */
    public getResultGroup(): ResultCodeGroup {
        return parseInt(`${this.getResult().code}`.charAt(0), 10);
    }

    /**
     *
     * @param {IResult} result
     */
    public setResult(result: IResult): void {
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
     *
     * @return {Measurement}
     */
    public getMeasurement(): Measurement {
        return this.measurement;
    }

    /**
     *
     */
    public getRequest(): IRequest {
        return this.request;
    }

    /**
     * @param {IRequest} request
     */
    public setRequest(request: IRequest): any {
        this.request = request;
    }

    /**
     *
     */
    public getResponse(): IResponse {
        return this.response;
    }

    /**
     * @param {IResponse} response
     */
    public setResponse(response: IResponse): any {
        this.response = response;
    }

}

export default JobMessage;
