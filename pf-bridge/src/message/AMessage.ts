import {INodeLabel} from "../topology/Configurator";
import Headers from "./Headers";
import {PFHeaders} from "./HeadersEnum";

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

        if (!node.id || !node.node_id || !node.node_name) {
            throw new Error(`Cannot create message object. Invalid node label obj: "${JSON.stringify(node)}"`);
        }

        this.headers = new Headers(h);
        this.headers.setPFHeader(PFHeaders.NODE_ID, node.node_id);
        this.headers.setPFHeader(PFHeaders.NODE_NAME, node.node_name);
    }

    public getNodeLabel(): INodeLabel {
        return this.node;
    }

    public getNodeId() {
        return this.node.id;
    }

    public getCorrelationId() {
        return this.headers.getPFHeader(PFHeaders.CORRELATION_ID);
    }

    public getProcessId() {
        return this.headers.getPFHeader(PFHeaders.PROCESS_ID);
    }

    public getParentId() {
        return this.headers.getPFHeader(PFHeaders.PARENT_ID);
    }

    public getSequenceId() {
        return parseInt(this.headers.getPFHeader(PFHeaders.SEQUENCE_ID), 10);
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
        this.body = new Buffer(content);
    }

}

export default AMessage;
