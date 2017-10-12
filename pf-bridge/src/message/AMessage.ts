import {INodeLabel} from "../topology/Configurator";
import {
    CORRELATION_ID_HEADER, default as Headers, PARENT_ID_HEADER, PROCESS_ID_HEADER, SEQUENCE_ID_HEADER,
} from "./Headers";

abstract class AMessage {

    constructor(
        protected node: INodeLabel,
        protected headers: Headers,
        protected body: Buffer,
    ) {
        Headers.validateMandatoryHeaders(headers.getRaw());

        if (!node.id || node.id === "") {
            throw new Error(`Cannot create message object. Invalid node info. "${node}"`);
        }

        if (headers.getRaw().sequenceId < 1) {
            throw new Error(`SequenceId must be greater than 0. "${headers.getRaw().sequenceId}"`);
        }
    }

    public getNodeLabel(): INodeLabel {
        return this.node;
    }

    public getNodeId() {
        return this.node.id;
    }

    public getCorrelationId() {
        return this.headers.getRaw()[CORRELATION_ID_HEADER];
    }

    public getProcessId() {
        return this.headers.getRaw()[PROCESS_ID_HEADER];
    }

    public getParentId() {
        return this.headers.getRaw()[PARENT_ID_HEADER];
    }

    public getSequenceId() {
        return this.headers.getRaw()[SEQUENCE_ID_HEADER];
    }

    /**
     * Returns custom headers amended by system headers
     *
     * @return {*}
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
