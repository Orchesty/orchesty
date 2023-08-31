import {INodeLabel} from "../topology/Configurator";
import Headers from "./Headers";

export enum MessageType {
    COUNTER = "counter",
    PROCESS = "process",
    SERVICE = "service",
}

abstract class AMessage {

    protected headers: Headers;

    /**
     * Constructor
     */
    constructor(
        protected node: INodeLabel,
        headers: { [key: string]: string },
        protected body: Buffer,
    ) {
        const h = Headers.getPFHeaders(headers);
        Headers.validateMandatoryHeaders(h);

        // if (!node.id || !node.node_id || !node.node_name) {
        if (!node.id || !node.node_id) {
            throw new Error(`Cannot create message object. Invalid node label obj: "${JSON.stringify(node)}"`);
        }

        this.headers = new Headers(h);
        this.headers.setPFHeader(Headers.NODE_ID, node.node_id);
        // this.headers.setPFHeader(Headers.NODE_NAME, node.node_name);
    }

    public getNodeLabel(): INodeLabel {
        return this.node;
    }

    public getNodeId() {
        return this.node.id;
    }

    public getCorrelationId() {
        return this.headers.getPFHeader(Headers.CORRELATION_ID);
    }

    public getProcessId() {
        return this.headers.getPFHeader(Headers.PROCESS_ID);
    }

    public getParentId() {
        return this.headers.getPFHeader(Headers.PARENT_ID);
    }

    public getSequenceId() {
        return parseInt(this.headers.getPFHeader(Headers.SEQUENCE_ID), 10);
    }

    /**
     *
     * @return {Headers}
     */
    public getHeaders(): Headers {
        return this.headers;
    }

    /**
     *
     * @param {Headers} headers
     */
    public setHeaders(headers: Headers) {
        this.headers = headers;
    }

    /**
     *
     * @return {Buffer}
     */
    public getBody(): Buffer {
        return this.body;
    }

    /**
     *
     * @return {string}
     */
    public getContent(): string {
        return this.body.toString();
    }

    /**
     *
     * @param {string} content
     */
    public setContent(content: string) {
        this.body = Buffer.from(content);
    }

}

export default AMessage;
