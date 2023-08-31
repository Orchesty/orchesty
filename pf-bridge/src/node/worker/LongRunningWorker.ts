import { Channel } from "amqplib";
import { Connection, Publisher } from "amqplib-plus";
import { Agent } from "http";
import { IMetrics } from "metrics-sender/dist/lib/metrics/IMetrics";
import * as request from "request";
import logger from "../../logger/Logger";
import Headers from "../../message/Headers";
import JobMessage, { IResult } from "../../message/JobMessage";
import { ResultCode } from "../../message/ResultCode";
import { INodeLabel } from "../../topology/Configurator";
import AWorker from "./AWorker";

export interface ILongRunningWorkerSettings {
    node_label: INodeLabel;
    host: string;
    port: number;
    method: string;
    process_path: string;
    status_path: string;
    secure: boolean;
    opts: any;
}

const DEFAULT_HTTP_TIMEOUT = 60000;

/**
 * Converts JobMessage to Http request and then converts received Http response back to JobMessage object
 */
class LongRunningWorker extends AWorker {

    private timeout: number = DEFAULT_HTTP_TIMEOUT;
    private agent: Agent = new Agent({ keepAlive: true, maxSockets: Infinity });

    private publisher: Publisher;
    private resultsQueue: { name: string, options: any, prefetch: number };

    /**
     * @param {ILongRunningWorkerSettings} settings
     * @param {Connection} connection
     * @param {IMetrics} metrics
     */
    constructor(
        protected settings: ILongRunningWorkerSettings,
        protected connection: Connection,
        protected metrics: IMetrics,
    ) {
        super();

        this.resultsQueue = {
            name: "pipes.long-running",
            options: { durable: true, exclusive: false, autoDelete: false },
            prefetch: 1,
        };

        const publisherPrepare = async (ch: Channel): Promise<void> => {
            await ch.assertQueue(this.resultsQueue.name, this.resultsQueue.options);
        };

        this.publisher = new Publisher(connection, publisherPrepare);
    }

    /**
     *
     * @param {Agent} agent
     */
    public setAgent(agent: Agent) {
        this.agent = agent;
    }

    /**
     * @param {number} timeout
     */
    public setTimeout(timeout: number) {
        this.timeout = timeout;
    }

    /**
     * Processes service type messages
     *
     * @param {JobMessage} msg
     * @return {JobMessage}
     */
    public async processService(msg: JobMessage): Promise<JobMessage> {
        msg.setResult({ code: ResultCode.SUCCESS, message: "Service message passed by." });

        return msg;
    }

    /**
     * Processes process type messages
     *
     * @param {JobMessage} msg
     * @return {Promise<JobMessage[]>}
     */
    public processData(msg: JobMessage): Promise<JobMessage[]> {
        const hasDocument = msg.getHeaders().hasPFHeader(Headers.DOCUMENT_ID);

        // add special header with next nods
        if (this.additionalHeaders !== undefined) {
            this.additionalHeaders.forEach((value: string, key: string) => {
                msg.getHeaders().setPFHeader(key, value);
            });
        }

        return new Promise((resolve, reject) => {
            if (hasDocument) {
                const reqParams: any = this.getJobRequestParams(msg);
                Object.assign(reqParams, this.settings.opts);

                logger.debug(
                    `Worker[type='long-running'] sent request to ${reqParams.url}. \
                     Headers: ${JSON.stringify(reqParams.headers)}`,
                    logger.ctxFromMsg(msg),
                );

                request(reqParams, (err: any, response: request.RequestResponse, body: string) => {
                    if (err) {
                        this.onRequestError(msg, reqParams, err);
                        return reject([msg]);
                    }

                    if (!response.statusCode || response.statusCode !== 200) {
                        this.onInvalidStatusCode(msg, reqParams, response.statusCode, body);
                        return reject([msg]);
                    }

                    const responseHeaders: any = response.headers;
                    try {
                        Headers.validateMandatoryHeaders(responseHeaders);
                    } catch (err) {
                        this.onInvalidResponseHeaders(msg);
                        return resolve([msg]);
                    }

                    const cleanResponseHeaders = new Headers(Headers.getPFHeaders(responseHeaders));
                    cleanResponseHeaders.removePFHeader(Headers.DOCUMENT_ID);
                    msg.setHeaders(cleanResponseHeaders);

                    let result: IResult;
                    try {
                        result = this.getResultFromResponse(cleanResponseHeaders);
                    } catch (err) {
                        this.onMissingResultCode(msg);
                        return resolve([msg]);
                    }

                    this.onValidResponse(msg, body, cleanResponseHeaders, result);
                    return resolve([msg]);
                });
            } else {
               return this.publisher.sendToQueue(
                    this.resultsQueue.name,
                    Buffer.from(msg.getContent()),
                    {
                        replyTo: this.resultsQueue.name,
                        headers: msg.getHeaders().getRaw(),
                    },
                ).then(() => resolve([]));
            }
        });
    }

    /**
     * Returns whether the worker is fully ready
     *
     * @return {Promise<boolean>}
     */
    public isWorkerReady(): Promise<boolean> {
        return new Promise((resolve) => {
            const nodeId = this.settings.node_label.id;
            const reqParams = { method: "GET", url: this.getUrl(this.settings.status_path) };

            logger.debug(`Worker[type='long-running'] asking worker if ready on ${reqParams.url}`, { node_id: nodeId });

            request(reqParams, (err: any, response: request.RequestResponse) => {
                if (err) {
                    logger.warn("Worker[type'long-running'] worker not ready.", { node_id: nodeId, error: err });

                    return resolve(false);
                }

                if (response.statusCode !== 200) {
                    logger.warn(
                        `Worker[type'long-running'] worker not ready: statusCode="${response.body}"`,
                        { node_id: nodeId },
                    );

                    return resolve(false);
                }

                logger.debug("Worker[type'long-running'] ready", { node_id: nodeId });

                return resolve(true);
            });
        });
    }

    /**
     * Creates http request body to be sent
     *
     * @param {JobMessage} inMsg
     * @return {string}
     */
    public getHttpRequestBody(inMsg: JobMessage): string {
        return inMsg.getContent();
    }

    /**
     *
     * @param {JobMessage} inMsg
     * @return {Headers}
     */
    public getHttpRequestHeaders(inMsg: JobMessage): Headers {
        const httpHeaders = new Headers(inMsg.getHeaders().getRaw());
        httpHeaders.setPFHeader(Headers.NODE_ID, this.settings.node_label.node_id);
        httpHeaders.setPFHeader(Headers.NODE_NAME, this.settings.node_label.node_name);

        return httpHeaders;
    }

    /**
     *
     * @param {JobMessage} inMsg
     * @return {request.Options}
     */
    private getJobRequestParams(inMsg: JobMessage): request.Options {
        const method = this.settings.method.toUpperCase();
        const httpParams: request.Options = {
            method: this.settings.method.toUpperCase(),
            url: this.getUrl(this.settings.process_path),
            followAllRedirects: true,
            headers: this.getHttpRequestHeaders(inMsg).getRaw(),
            agent: this.agent,
            timeout: this.timeout,
        };

        if (method === "POST" || method === "PATCH" || method === "PUT") {
            httpParams.body = this.getHttpRequestBody(inMsg);
        }

        return httpParams;
    }

    /**
     *
     * @param {string} path
     * @return {string}
     */
    private getUrl(path: string): string {
        const protocol = this.settings.secure ? "https://" : "http://";
        const port = this.settings.port || 80;

        return `${protocol}${this.settings.host}:${port}${path}`;
    }

    /**
     *
     * @param {Headers} headers
     * @return {IResult}
     */
    private getResultFromResponse(headers: Headers): IResult {
        const resultCode = parseInt(headers.getPFHeader(Headers.RESULT_CODE), 10);
        const resultMessage = headers.getPFHeader(Headers.RESULT_MESSAGE) || "";

        if (!(resultCode in ResultCode)) {
            throw new Error("Missing or invalid result code.");
        }

        return { code: resultCode, message: resultMessage };
    }

    /**
     * Handles valid http request and updates JobMessage
     * @param {JobMessage} msg
     * @param {string} responseBody
     * @param {Headers} responseHeaders
     * @param {IResult} result
     */
    private onValidResponse(
        msg: JobMessage,
        responseBody: string,
        responseHeaders: Headers,
        result: IResult,
    ) {
        logger.debug("Worker[type='long-running'] received valid response.", logger.ctxFromMsg(msg));

        if (!responseBody) {
            responseBody = "";
        }

        if (typeof responseBody !== "string") {
            responseBody = JSON.stringify(responseBody);
        }

        msg.setHeaders(responseHeaders);
        msg.setContent(responseBody);
        msg.setResult(result);
    }

    /**
     *
     * @param {JobMessage} msg
     * @param reqParams
     * @param err
     */
    private onRequestError(msg: JobMessage, reqParams: request.Options, err: any): void {
        if (err.code === "ETIMEDOUT" || err.code === "ESOCKETTIMEDOUT") {
            logger.warn(`Worker[type='long-running'] http timeout error. \
                Repeating message.`, logger.ctxFromMsg(msg, err));
            msg.setResult({ code: ResultCode.REPEAT, message: err.message });

            const h = msg.getHeaders();
            h.setPFHeader(Headers.REPEAT_INTERVAL, "0");
            h.setPFHeader(Headers.REPEAT_HOPS, h.getPFHeader(Headers.REPEAT_HOPS) || "1");
            h.setPFHeader(Headers.REPEAT_MAX_HOPS, h.getPFHeader(Headers.REPEAT_MAX_HOPS) || "5");

            return;
        }

        logger.warn(`Worker[type='long-running'] http error: ${err}.`, logger.ctxFromMsg(msg, err));
        msg.setResult({
            code: ResultCode.HTTP_ERROR,
            message: err,
        });
    }

    /**
     *
     * @param {JobMessage} msg
     * @param {request.Options} req
     * @param {number} statusCode
     * @param {string} response
     */
    private onInvalidStatusCode(msg: JobMessage, req: request.Options, statusCode: number, response: string): void {
        const context = logger.ctxFromMsg(msg);
        context.data = JSON.stringify({ request: { body: req.body }, response });
        logger.warn(
            `Worker[type='long-running'] received response with statusCode="${statusCode}"`,
            context,
        );
        msg.setResult(
            {
                code: ResultCode.HTTP_ERROR,
                message: `Http response with invalid status code "${statusCode}" received`,
            },
        );
    }

    /**
     *
     * @param {JobMessage} msg
     */
    private onInvalidResponseHeaders(msg: JobMessage): void {
        logger.warn(
            `Worker[type='long-running'] received response with missing mandatory headers.`,
            logger.ctxFromMsg(msg),
        );
        msg.setResult({ code: ResultCode.INVALID_HEADERS, message: "Invalid headers" });
    }

    /**
     *
     * @param {JobMessage} msg
     */
    private onMissingResultCode(msg: JobMessage): void {
        logger.warn(
            `Worker[type='long-running'] received response with missing result code header.`,
            logger.ctxFromMsg(msg),
        );
        msg.setResult({ code: ResultCode.MISSING_RESULT_CODE, message: "Missing result code header" });
    }

}

export default LongRunningWorker;
