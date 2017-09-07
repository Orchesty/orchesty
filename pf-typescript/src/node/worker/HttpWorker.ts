import logger from "lib-nodejs/dist/src/logger/Logger";
import * as request from "request";
import JobMessage from "../../message/JobMessage";
import { ResultCode } from "../../message/ResultCode";
import AHttpWorker from "./http/AHttpWorker";

export interface IHttpWorkerSettings {
    method: string;
    url: string;
    opts: any;
}

class HttpWorker extends AHttpWorker {

    private opts: {};

    constructor(settings: IHttpWorkerSettings) {
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

            logger.info(`HttpWorker "${reqParams.method}" request to: ${reqParams.url} [id=${msg.getUuid()}]`);

            // Make http request and wait for response
            request(reqParams, (err, response, body) => {
                if (err) {
                    logger.error(`HttpWorker response[id=${msg.getUuid()}], Error: ${err}`);
                    msg.setResult({ status: ResultCode.HTTP_ERROR, message: err });

                    return resolve(msg);
                }

                if (!response.statusCode || response.statusCode !== 200) {
                    logger.error(`HttpWorker response[id=${msg.getUuid()}], Status code ${response.statusCode}`);
                    msg.setResult(
                        {
                            status: ResultCode.HTTP_ERROR,
                            message: `Http response with code ${response.statusCode} received`,
                        },
                    );
                    return resolve(msg);
                }

                logger.info(`HttpWorker response[id=${msg.getUuid()}] received.`);

                msg.setResult({ status: ResultCode.SUCCESS, message: "Http worker OK." });
                msg.setContent(JSON.stringify(body));

                return resolve(msg);
            });
        });
    }

}

export default HttpWorker;
