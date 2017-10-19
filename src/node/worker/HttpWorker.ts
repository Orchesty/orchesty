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

    constructor(protected settings: IHttpWorkerSettings) {}

    /**
     *
     * @param {JobMessage} msg
     * @return {Promise<JobMessage>}
     */
    public processData(msg: JobMessage): Promise<JobMessage> {
        const reqParams = this.getJobRequestParams(msg);

        return new Promise((resolve) => {
            Object.assign(reqParams, this.settings.opts);

            logger.info(`Worker[type='http'] sent HTTP request: ${JSON.stringify(reqParams)}`, logger.ctxFromMsg(msg));

            request(reqParams, (err: any, response: request.RequestResponse, body: string) => {
                if (err) {
                    this.onRequestError(msg, reqParams, err);
                    return resolve(msg);
                }

                if (!response.statusCode || response.statusCode !== 200) {
                    this.onInvalidStatusCode(msg, response.statusCode);
                    return resolve(msg);
                }

                let result: IResult;
                try {
                    result = this.getResultFromResponse(response);
                } catch (err) {
                    this.onMissingResultCode(msg);
                    return resolve(msg);
                }

                logger.info("Worker[type='http'] received valid response.", logger.ctxFromMsg(msg, err));

                if (!body) {
                    body = "";
                }

                if (typeof body !== "string") {
                    body = JSON.stringify(body);
                }

                msg.setResult(result);
                msg.setContent(body);

                return resolve(msg);
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
     * @param {request.RequestResponse} response
     * @return {IResult}
     */
    private getResultFromResponse(response: request.RequestResponse): IResult {
        const responseHeaders: any = response.headers;
        const resultHeaders = new Headers(responseHeaders);

        const resultCode = parseInt(resultHeaders.getPFHeader(Headers.RESULT_CODE), 10);
        const resultMessage = resultHeaders.getPFHeader(Headers.RESULT_MESSAGE) || "";

        if (!(resultCode in ResultCode)) {
            throw new Error("Missing or invalid result code.");
        }

        return {code: resultCode, message: resultMessage};
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
    private onMissingResultCode(msg: JobMessage): void {
        logger.warn(
            `Worker[type='http'] received response with missing result code header.`,
            logger.ctxFromMsg(msg),
        );
        msg.setResult({ code: ResultCode.MISSING_RESULT_CODE, message: "Missing result code header"});
    }

}

export default HttpWorker;
