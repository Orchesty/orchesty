import {INodeLabel} from "../topology/Configurator";
import {CORRELATION_ID_HEADER, PARENT_ID_HEADER, PROCESS_ID_HEADER, SEQUENCE_ID_HEADER} from "./Headers";

abstract class AMessage {

    /**
     *
     * @param {INodeLabel} node
     * @param {string} correlationId
     * @param {string} processId
     * @param {string} parentId
     * @param {number} sequenceId
     * @param {{[p: string]: string}} headers
     * @param {Buffer} body
     */
    constructor(
        protected node: INodeLabel,
        protected correlationId: string,
        protected processId: string,
        protected parentId: string,
        protected sequenceId: number,
        protected headers: { [key: string]: string },
        protected body: Buffer,
    ) {
        if (!node.id || node.id === "") {
            throw new Error(`Cannot create message object. Invalid node info. "${node}"`);
        }

        if (!sequenceId || sequenceId < 1) {
            throw new Error(`Cannot create message object. Invalid sequenceId. "${sequenceId}"`);
        }

        this.setHeaders(headers);
    }

    public getNodeLabel(): INodeLabel {
        return this.node;
    }

    public getNodeId() {
        return this.node.id;
    }

    public getCorrelationId() {
        return this.correlationId;
    }

    public getProcessId() {
        return this.processId;
    }

    public getParentId() {
        return this.parentId;
    }

    public getSequenceId() {
        return this.sequenceId;
    }

    /**
     * Returns custom headers amended by system headers
     *
     * @return {*}
     */
    public getHeaders(): any {
        const h = this.headers;

        h[CORRELATION_ID_HEADER] = this.getCorrelationId();
        h[PROCESS_ID_HEADER] = this.getProcessId();
        h[PARENT_ID_HEADER] = this.getParentId();
        h[SEQUENCE_ID_HEADER] = `${this.getSequenceId()}`;

        return h;
    }

    /**
     * Cleans headers from system headers and set them to header field
     *
     * @param {{[p: string]: string}} headers
     */
    public setHeaders(headers: { [key: string]: string }): void {

        delete headers[CORRELATION_ID_HEADER];
        delete headers[PROCESS_ID_HEADER];
        delete headers[PARENT_ID_HEADER];
        delete headers[SEQUENCE_ID_HEADER];

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
        this.body = new Buffer(content);
    }

}

export default AMessage;
