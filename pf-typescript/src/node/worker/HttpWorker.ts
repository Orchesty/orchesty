import * as request from "request";
import logger from "../../logger/Logger";
import JobMessage from "../../message/JobMessage";
import { ResultCode } from "../../message/ResultCode";
import AHttpWorker from "./http/AHttpWorker";
import IWorker from "./IWorker";

export interface IHttpWorkerSettings {
    node_id: string;
    method: string;
    url: string;
    opts: any;
}

class HttpWorker extends AHttpWorker implements IWorker {

    private opts: {};

    constructor(private settings: IHttpWorkerSettings) {
        super(settings.method, settings.url);
        this.opts = settings.opts;
    }

    /**
     *
     * @param {JobMessage} msg
     * @return {Promise<JobMessage>}
     */
    public processData(msg: JobMessage): Promise<JobMessage> {
        const reqParams = this.getHttpRequestParams(msg);

        return new Promise((resolve) => {
            Object.assign(reqParams, this.opts);

            logger.info(
                `Worker[type'http'] sent request to: ${reqParams.url}`,
                { node_id: this.settings.node_id, correlation_id: msg.getJobId() },
            );

            // Make http request and wait for response
            request(reqParams, (err, response, body) => {
                if (err) {
                    logger.warn(
                        "Worker[type'http'] received response",
                        { node_id: this.settings.node_id, correlation_id: msg.getJobId(), error: err },
                    );
                    msg.setResult({ status: ResultCode.HTTP_ERROR, message: err });

                    return resolve(msg);
                }

                if (!response.statusCode || response.statusCode !== 200) {
                    logger.warn(
                        `Worker[type'http'] received response with statusCode="${response.statusCode}"`,
                        { node_id: this.settings.node_id, correlation_id: msg.getJobId() },
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
                logger.info(
                    "Worker[type'http'] received response",
                    { node_id: this.settings.node_id, correlation_id: msg.getJobId()},
                );

                msg.setResult({ status: ResultCode.SUCCESS, message: "Http worker OK." });
                msg.setContent(JSON.stringify(body));

                return resolve(msg);
            });
        });
    }

}

export default HttpWorker;
