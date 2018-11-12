import Resequencer from "../node/Resequencer";
import {INodeLabel} from "../topology/Configurator";

class Headers {

    public static readonly PF_HEADERS_PREFIX = "pf-";

    // mandatory headers
    public static readonly NODE_ID = "node-id";
    public static readonly NODE_NAME = "node-name";
    public static readonly CORRELATION_ID = "correlation-id";
    public static readonly PROCESS_ID = "process-id";
    public static readonly PARENT_ID = "parent-id";
    public static readonly SEQUENCE_ID = "sequence-id";

    // topology related headers
    public static readonly TOPOLOGY_ID = "topology-id";
    public static readonly TOPOLOGY_NAME = "topology-name";
    public static readonly TOPOLOGY_DELETE_URL = "topology-delete-url";

    // result headers
    public static readonly RESULT_CODE = "result-code";
    public static readonly RESULT_MESSAGE = "result-message";

    // repeat headers
    public static readonly REPEAT_QUEUE = "repeat-queue";
    public static readonly REPEAT_INTERVAL = "repeat-interval";
    public static readonly REPEAT_MAX_HOPS = "repeat-max-hops";
    public static readonly REPEAT_HOPS = "repeat-hops";

    // force queue forwarding
    public static readonly FORCE_TARGET_QUEUE = "force-target-queue";

    // limit headers
    public static readonly LIMIT_KEY = "limit-key";
    public static readonly LIMIT_TIME = "limit-time";
    public static readonly LIMIT_VALUE = "limit-value";
    public static readonly LIMIT_RETURN_EXCHANGE = "pf-limit-return-exchange";
    public static readonly LIMIT_RETURN_ROUTING_KEY = "pf-limit-return-routing-key";

    // Other headers
    public static readonly PUBLISHED_TIMESTAMP = "published-timestamp";
    public static readonly CONTENT_TYPE = "content-type";
    public static readonly DOCUMENT_ID = "doc-id";

    public static readonly HEADERS_WHITELIST = [
        Headers.CONTENT_TYPE,
    ];

    /**
     *
     * @return {{}}
     * @param headers
     */
    public static getPFHeaders(headers: { [key: string]: any }): { [key: string]: string } {
        const pfHeaders: any = {};

        Object.keys(headers).forEach((key: any) => {
            if (key.substr(0, Headers.PF_HEADERS_PREFIX.length) === Headers.PF_HEADERS_PREFIX) {
                pfHeaders[key] = headers[key];
                return;
            }

            if (Headers.HEADERS_WHITELIST.indexOf(key) > -1) {
                pfHeaders[key] = headers[key];
                return;
            }
        });

        return pfHeaders;
    }

    /**
     *
     * @param headers
     */
    public static validateMandatoryHeaders(headers: { [key: string]: string }) {
        if (!headers) {
            throw new Error("Invalid headers. Is it object?");
        }

        const errors = [];

        let key = `${Headers.PF_HEADERS_PREFIX}${Headers.CORRELATION_ID}`;
        if (!(key in headers) || !headers[key]) {
            errors.push(key);
        }

        key = `${Headers.PF_HEADERS_PREFIX}${Headers.PROCESS_ID}`;
        if (!(key in headers) || !headers[key]) {
            errors.push(key);
        }

        key = `${Headers.PF_HEADERS_PREFIX}${Headers.SEQUENCE_ID}`;
        if (!(key in headers) || parseInt(headers[key], 10) < Resequencer.START_SEQUENCE_ID) {
            errors.push(key);
        }

        // parent-id must be present but can be empty
        key = `${Headers.PF_HEADERS_PREFIX}${Headers.PARENT_ID}`;
        if (!(key in headers)) {
            errors.push(key);
        }

        if (errors.length > 0) {
            throw new Error(`Invalid '${errors.join(",")}' headers. Headers provided: ${JSON.stringify(headers)}`);
        }
    }

    /**
     *
     * @param headers
     * @return {boolean}
     */
    public static containsAllMandatory(headers: { [key: string]: string }): boolean {
        try {
            Headers.validateMandatoryHeaders(headers);
            return true;
        } catch (err) {
            return false;
        }
    }

    constructor(private headers?: { [key: string]: string }) {
        if (!headers) {
            this.headers = {};
        }
    }

    /**
     *
     * @param {string} key
     * @param value
     */
    public setHeader(key: string, value: string) {
        this.headers[key] = value;
    }

    /**
     *
     * @param {string} key
     * @param value
     */
    public setPFHeader(key: string, value: string) {
        this.headers[`${Headers.PF_HEADERS_PREFIX}${key}`] = value;
    }

    /**
     *
     * @param {string} key
     * @return {string}
     */
    public getHeader(key: string): string {
        return this.headers[key];
    }

    /**
     *
     * @param {string} key
     * @return {string}
     */
    public getPFHeader(key: string): string {
        return this.headers[`${Headers.PF_HEADERS_PREFIX}${key}`];
    }

    /**
     *
     * @param {string} key
     * @return {boolean}
     */
    public hasPFHeader(key: string): boolean {
        if (`${Headers.PF_HEADERS_PREFIX}${key}` in this.headers) {
            return true;
        }

        return false;
    }

    /**
     *
     * @return any
     */
    public getRaw(): any {
        return JSON.parse(JSON.stringify(this.headers));
    }

    /**
     *
     * @param {string} key
     */
    public removePFHeader(key: string) {
        delete this.headers[`${Headers.PF_HEADERS_PREFIX}key`];
    }

    /**
     *
     * @param {string} key
     */
    public removeHeader(key: string) {
        delete this.headers[key];
    }

    /**
     *
     * @return {INodeLabel}
     */
    public createNodeLabel(): INodeLabel {
        return {
            id: this.getPFHeader(Headers.NODE_ID),
            node_id: this.getPFHeader(Headers.NODE_ID),
            node_name: this.getPFHeader(Headers.NODE_NAME),
            topology_id: this.getPFHeader(Headers.TOPOLOGY_ID),
        };
    }

}

export default Headers;
