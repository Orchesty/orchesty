import * as request from "request";
import logger from "../../logger/Logger";
import JobMessage from "../../message/JobMessage";
import { ResultCode } from "../../message/ResultCode";
import IWorker from "./IWorker";

export interface IHttpWorkerSettings {
    node_id: string;
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
    json: any;
    gzip?: boolean;
    body?: string;
    headers: {
        correlation_id: string,
        process_id: string,
        parent_id: string,
        sequence_id: number,
        reply_to_url?: string,
        reply_to_method?: string,
    };
}

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
            request(reqParams, (err, response, body) => {
                if (err) {
                    logger.warn(
                        `Worker[type='http'] did not received response. \
                         Request params: ${JSON.stringify(reqParams)}. Body: ${JSON.stringify(body)}`,
                        logger.ctxFromMsg(msg, err),
                    );
                    msg.setResult({ status: ResultCode.HTTP_ERROR, message: err });

                    return resolve(msg);
                }

                if (!response.statusCode || response.statusCode !== 200) {
                    logger.warn(
                        `Worker[type='http'] received response with statusCode="${response.statusCode}"`,
                        logger.ctxFromMsg(msg, err),
                    );
                    msg.setResult(
                        {
                            status: ResultCode.HTTP_ERROR,
                            message: `Http response with code ${response.statusCode} received`,
                        },
                    );

                    return resolve(msg);
                }

                // Everything OK
                logger.info("Worker[type='http'] received response", logger.ctxFromMsg(msg, err));

                msg.setResult({ status: ResultCode.SUCCESS, message: "Http worker OK." });
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
            const reqParams = { method: "GET", url: this.getUrl(this.settings.status_path)};

            logger.info(`HttpWorker asking worker if is ready on ${reqParams.url}`, {node_id: this.settings.node_id});

            request(reqParams, (err, response) => {
                if (err) {
                    logger.warn("HttpWorker worker not ready.", { node_id: this.settings.node_id, error: err });

                    return resolve(false);
                }

                if (response.statusCode !== 200) {
                    logger.warn(
                        `HttpWorker worker not ready: statusCode="${response.statusCode}"`,
                        { node_id: this.settings.node_id },
                    );

                    return resolve(false);
                }

                logger.info("Worker[type'http'] ready", { node_id: this.settings.node_id });

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
        return {
            method: this.settings.method.toUpperCase(),
            url: this.getUrl(this.settings.process_path),
            json: JSON.parse(inMsg.getContent()),
            headers: {
                correlation_id: inMsg.getCorrelationId(),
                process_id: inMsg.getProcessId(),
                parent_id: inMsg.getParentId(),
                sequence_id: inMsg.getSequenceId(),
            },
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

}

export default HttpWorker;
