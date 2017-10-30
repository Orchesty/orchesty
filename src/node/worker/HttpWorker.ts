import IMetrics from "lib-nodejs/dist/src/metrics/IMetrics";
import TimeUtils from "lib-nodejs/dist/src/utils/TimeUtils";
import * as request from "request";
import logger from "../../logger/Logger";
import Headers from "../../message/Headers";
import JobMessage, {IResult} from "../../message/JobMessage";
import { ResultCode } from "../../message/ResultCode";
import {INodeLabel} from "../../topology/Configurator";
import IWorker from "./IWorker";

export interface IHttpWorkerSettings {
    node_label: INodeLabel;
    host: string;
    port: number;
    method: string;
    process_path: string;
    status_path: string;
    secure: boolean;
    opts: any;
}

/**
 * Converts JobMessage to Http request and then converts received Http response back to JobMessage object
 */
class HttpWorker implements IWorker {

    constructor(
        protected settings: IHttpWorkerSettings,
        protected metrics: IMetrics,
    ) {}

    /**
     *
     * @param {JobMessage} msg
     * @return {Promise<JobMessage[]>}
     */
    public processData(msg: JobMessage): Promise<JobMessage[]> {
        const reqParams: any = this.getJobRequestParams(msg);

        return new Promise((resolve) => {
            Object.assign(reqParams, this.settings.opts);

            logger.info(
                `Worker[type='http'] sent request to ${reqParams.url}. Headers: ${JSON.stringify(reqParams.headers)}`,
                logger.ctxFromMsg(msg),
            );

            const sent = TimeUtils.nowMili();
            request(reqParams, (err: any, response: request.RequestResponse, body: string) => {
                this.sendHttpRequestMetrics(msg, err, response, sent);

                if (err) {
                    this.onRequestError(msg, reqParams, err);
                    return resolve([msg]);
                }

                if (!response.statusCode || response.statusCode !== 200) {
                    this.onInvalidStatusCode(msg, response.statusCode);
                    return resolve([msg]);
                }

                const responseHeaders: any = response.headers;
                try {
                    Headers.validateMandatoryHeaders(responseHeaders);
                } catch (err) {
                    this.onInvalidResponseHeaders(msg);
                    return resolve([msg]);
                }

                const cleanResponseHeaders = new Headers(Headers.getPFHeaders(responseHeaders));
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
        });
    }

    /**
     * Returns whether the worker is ready or not
     *
     * @return {Promise<boolean>}
     */
    public isWorkerReady(): Promise<boolean> {
        return new Promise((resolve) => {
            const nodeId = this.settings.node_label.id;
            const reqParams = { method: "GET", url: this.getUrl(this.settings.status_path)};

            logger.info(`HttpWorker asking worker if is ready on ${reqParams.url}`, {node_id: nodeId});

            request(reqParams, (err, response) => {
                if (err) {
                    logger.warn("HttpWorker worker not ready.", { node_id: nodeId, error: err });

                    return resolve(false);
                }

                if (response.statusCode !== 200) {
                    logger.warn(
                        `HttpWorker worker not ready: statusCode="${response.statusCode}"`,
                        { node_id: nodeId },
                    );

                    return resolve(false);
                }

                logger.info("Worker[type'http'] ready", { node_id: nodeId });

                return resolve(true);
            });
        });
    }

    /**
     * Creates http request body to be sent
     *
     * @param {JobMessage} inMsg
     * @return {any}
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

        return {code: resultCode, message: resultMessage};
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
        logger.info("Worker[type='http'] received valid response.", logger.ctxFromMsg(msg));

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
        logger.warn(
            `Worker[type='http'] did not receive response. Request params: ${JSON.stringify(reqParams)}.`,
            logger.ctxFromMsg(msg, err),
        );
        msg.setResult({ code: ResultCode.HTTP_ERROR, message: err });
    }

    /**
     *
     * @param {JobMessage} msg
     * @param {number} statusCode
     */
    private onInvalidStatusCode(msg: JobMessage, statusCode: number): void {
        logger.warn(
            `Worker[type='http'] received response with statusCode="${statusCode}"`,
            logger.ctxFromMsg(msg),
        );
        msg.setResult(
            {
                code: ResultCode.HTTP_ERROR,
                message: `Http response with code ${statusCode} received`,
            },
        );
    }

    /**
     *
     * @param {JobMessage} msg
     */
    private onInvalidResponseHeaders(msg: JobMessage): void {
        logger.warn(
            `Worker[type='http'] received response with missing mandatory headers.`,
            logger.ctxFromMsg(msg),
        );
        msg.setResult({ code: ResultCode.INVALID_HEADERS, message: "Invalid headers"});
    }

    /**
     *
     * @param {JobMessage} msg
     */
    private onMissingResultCode(msg: JobMessage): void {
        logger.warn(
            `Worker[type='http'] received response with missing result code header.`,
            logger.ctxFromMsg(msg),
        );
        msg.setResult({ code: ResultCode.MISSING_RESULT_CODE, message: "Missing result code header"});
    }

    /**
     *
     * @param {JobMessage} msg
     * @param err
     * @param {request.RequestResponse} response
     * @param {number} sent
     */
    private sendHttpRequestMetrics(msg: JobMessage, err: any, response: request.RequestResponse, sent: number) {
        const duration = TimeUtils.nowMili() - sent;

        let resultCode: any;
        if (!err && response.headers && response.headers[Headers.RESULT_CODE]) {
            resultCode = response.headers[Headers.RESULT_CODE];
        }

        this.metrics.send({
            http_worker_process_duration: duration,
            http_worker_error: !!err,
            http_worker_result_code: parseInt(resultCode, 10),
        }).catch((mErr) => {
                logger.warn("Unable to send metrics", logger.ctxFromMsg(msg, mErr));
            });
    }

}

export default HttpWorker;
