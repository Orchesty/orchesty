import * as request from "request";
import JobMessage from "../../message/JobMessage";
import { ResultCode } from "../../message/ResultCode";
import AHttpWorker from "./http/AHttpWorker";

class HttpWorker extends AHttpWorker {

    private opts: {};

    constructor(method: string, url: string, opts: {}) {
        super(method, url);
        this.opts = opts;
    }

    /**
     *
     * @param {JobMessage} msg
     * @return {Promise<JobMessage>}
     */
    public processData(msg: JobMessage): Promise<JobMessage> {
        const reqParams = this.getHttpRequestParams(msg);

        return new Promise((resolve, reject) => {

            Object.assign(reqParams, this.opts);

            // Make http request and wait for response
            request(reqParams, (err, response, body) => {
                if (err) {
                    msg.setJobResultFailed(ResultCode.HTTP_ERROR, err);
                    return reject(msg);
                }

                if (!response.statusCode || response.statusCode !== 200) {
                    msg.setJobResultFailed(
                        ResultCode.HTTP_ERROR, `Http response with code ${response.statusCode} received`,
                    );
                    return reject(msg);
                }

                msg.setContent(body);
                msg.setJobResultOK();

                return resolve(msg);
            });
        });
    }

}

module.exports = HttpWorker;
