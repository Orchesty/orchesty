import * as request from "request";
import logger from "../../logger/Logger";
import Headers from "../../message/Headers";
import JobMessage from "../../message/JobMessage";
import { ResultCode } from "../../message/ResultCode";
import {INodeLabel} from "../../topology/Configurator";
import IWorker from "./IWorker";
import {PFHeaders} from "../../message/HeadersEnum";

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

export interface IHttpWorkerRequestParams {
    method: string;
    url: string;
    json?: any;
    followAllRedirects?: boolean;
    gzip?: boolean;
    body?: string;
    headers?: any;
}

const DEFAULT_EMPTY_BODY = { data: {}, settings: {}};

/**
 * Converts JobMessage to Http request and then converts received Http response back to JobMessage object
 */
class HttpWorker implements IWorker {

    constructor(private settings: IHttpWorkerSettings) {}

    /**
     *
     * @param {JobMessage} msg
     * @return {Promise<JobMessage>}
     */
    public processData(msg: JobMessage): Promise<JobMessage> {
        const reqParams = this.getJobRequestParams(msg);

        return new Promise((resolve) => {
            Object.assign(reqParams, this.settings.opts);

            logger.info(`Worker[type='http'] sent request to: ${reqParams.url}`, logger.ctxFromMsg(msg));

            // Make http request and wait for response
            request(reqParams, (err: any, response: any, body: any) => {
                if (err) {
                    this.onRequestError(msg, reqParams, err);
                    return resolve(msg);
                }

                if (!response.statusCode || response.statusCode !== 200) {
                    this.onInvalidStatusCode(msg, response.statusCode);
                    return resolve(msg);
                }

                const resultHeaders = new Headers(response.headers);
                const claimedCode = parseInt(resultHeaders.getPFHeader(PFHeaders.RESULT_CODE), 10);
                if (!(claimedCode in ResultCode)) {
                    this.onMissingResultCode(msg);
                    return resolve(msg);
                }

                // Worker sent result code, react on it's value
                const resultMessage = response.headers.result_message;

                if (claimedCode === ResultCode.SUCCESS) {
                    logger.info("Worker[type='http'] received 'SUCCESS' response", logger.ctxFromMsg(msg, err));
                } else {
                    logger.warn(
                        `Worker[type='http'] received response code: "${claimedCode}"`,
                        logger.ctxFromMsg(msg, err),
                    );
                }

                // On empty body set default content
                if (!body) {
                    body = DEFAULT_EMPTY_BODY;
                }

                // Set the received result code and message body
                msg.setResult({ code: claimedCode, message: resultMessage });
                msg.setContent(JSON.stringify(body));

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
     *
     * @param {JobMessage} inMsg
     * @return {IHttpWorkerRequestParams}
     */
    private getJobRequestParams(inMsg: JobMessage): IHttpWorkerRequestParams {

        const headersToSend = new Headers(inMsg.getHeaders().getRaw());
        headersToSend.setPFHeader(PFHeaders.NODE_ID, this.settings.node_label.node_id);
        headersToSend.setPFHeader(PFHeaders.NODE_NAME, this.settings.node_label.node_name);

        return {
            method: this.settings.method.toUpperCase(),
            url: this.getUrl(this.settings.process_path),
            json: JSON.parse(inMsg.getContent()),
            followAllRedirects: true,
            headers: headersToSend.getRaw(),
        };
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
     * @param {JobMessage} msg
     * @param reqParams
     * @param err
     */
    private onRequestError(msg: JobMessage, reqParams: IHttpWorkerRequestParams, err: any): void {
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
