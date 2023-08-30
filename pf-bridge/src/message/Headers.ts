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
    public static readonly PROCESS_STARTED = "process-started";

    // topology related headers
    public static readonly TOPOLOGY_ID = "topology-id";
    public static readonly TOPOLOGY_NAME = "topology-name";
    public static readonly TOPOLOGY_DELETE_URL = "topology-delete-url";

    //
    // message processing result
    //
    // what was the result of message processing, 0=success
    public static readonly RESULT_CODE = "result-code";
    // optional comment about message processing result
    public static readonly RESULT_MESSAGE = "result-message";

    //
    // repeater headers
    //
    // where the message should be published to when repeated from repeater
    public static readonly REPEAT_QUEUE = "repeat-queue";
    // how many seconds the message should be hold in repeater
    public static readonly REPEAT_INTERVAL = "repeat-interval";
    // how many times the message can be maximally be repeated
    public static readonly REPEAT_MAX_HOPS = "repeat-max-hops";
    // how many times yhe message has been already repeated (value being incremented on each repeat)
    public static readonly REPEAT_HOPS = "repeat-hops";

    //
    // sending message to next nodes related headers
    //
    // if RESULT_CODE ended with FORWARD_TO_TARGET_QUEUE, the message will be sent to queue in FORCE_TARGET_QUEUE
    public static readonly FORCE_TARGET_QUEUE = "force-target-queue";
    // when given, it should contain the list of follower queues separated by commas
    // the message will be forwarded to given queues only instead of all followers
    public static readonly WHITELIST_FOLLOWERS = "whitelist-followers";
    // when given, it should contain the list of follower queues separated by commas
    // the message will NOT be forwarded to given queues
    public static readonly BLACKLIST_FOLLOWERS = "blacklist-followers";

    //
    // limiter related headers
    //
    // limit headers
    // DEPRECATED
    public static readonly LIMIT_KEY = "limit-key";
    public static readonly LIMIT_TIME = "limit-time";
    public static readonly LIMIT_VALUE = "limit-value";
    // end of DEPRECATED

    public static readonly LIMITER_KEY = "limiter-key";
    public static readonly LIMIT_RETURN_EXCHANGE = "limit-return-exchange";
    public static readonly LIMIT_RETURN_ROUTING_KEY = "limit-return-routing-key";
    public static readonly LIMIT_MESSAGE_FROM_LIMITER = "limit-message-from-limiter";

    //
    // Other headers
    //
    // if message published from starting point (used in Counter messages to start process time duration statistics)
    // set to 1 if publishing from starting point
    public static readonly FROM_STARTING_POINT = "from-starting-point";
    // when the message was published from publisher (used in consumer to count message waiting duration in queue)
    public static readonly PUBLISHED_TIMESTAMP = "published-timestamp";
    // content-type of the message
    public static readonly CONTENT_TYPE = "content-type";
    public static readonly DOCUMENT_ID = "doc-id";

    // base64 encoded JSON with node followers
    public static readonly WORKER_FOLLOWERS = "worker-followers";

    // Header for name of return queue from batch workers
    public static readonly REPLY_TO = "reply-to";

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
     * @return string
     */
    public toString(): string {
        return JSON.stringify(this.headers);
    }

    /**
     *
     * @param {string} key
     */
    public removePFHeader(key: string) {
        delete this.headers[`${Headers.PF_HEADERS_PREFIX}${key}`];
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
