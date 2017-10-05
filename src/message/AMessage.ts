abstract class AMessage {

    constructor(
        protected nodeId: string,
        protected correlationId: string,
        protected processId: string,
        protected parentId: string,
        protected sequenceId: number,
        protected headers: { [key: string]: string },
        protected body: Buffer,
    ) {
        if (!nodeId || nodeId === "") {
            throw new Error(`Cannot create message object. Invalid nodeId. "${nodeId}"`);
        }
        if (!correlationId || correlationId === "") {
            throw new Error(`Cannot create message object. Invalid correlationId. "${correlationId}"`);
        }
        if (!processId || processId === "") {
            throw new Error(`Cannot create message object. Invalid processId. "${processId}"`);
        }
        // if (!parentId) {
        //     throw new Error(`Cannot create message object. Invalid parentId. "${parentId}"`);
        // }
        if (!sequenceId || sequenceId < 1) {
            throw new Error(`Cannot create message object. Invalid sequenceId. "${sequenceId}"`);
        }

        this.setHeaders(headers);
    }

    public getNodeId() {
        return this.nodeId;
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

        h.correlation_id = this.getCorrelationId();
        h.process_id = this.getProcessId();
        h.parent_id = this.getParentId();
        h.sequence_id = `${this.getSequenceId()}`;

        return h;
    }

    /**
     * Cleans headers from system headers and set them to header field
     *
     * @param {{[p: string]: string}} headers
     */
    public setHeaders(headers: { [key: string]: string }): void {
        delete headers.correlation_id;
        delete headers.process_id;
        delete headers.parent_id;
        delete headers.sequence_id;

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
