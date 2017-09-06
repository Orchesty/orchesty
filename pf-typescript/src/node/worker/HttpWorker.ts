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
     * @param {JobMessage} inMsg
     * @return {Promise<JobMessage>}
     */
    public processData(inMsg: JobMessage): Promise<JobMessage> {
        const reqParams = this.getHttpRequestParams(inMsg);

        return new Promise((resolve, reject) => {

            Object.assign(reqParams, this.opts);

            // Make http request and wait for response
            request(reqParams, (err, response, body) => {
                if (err) {
                    return reject(
                        AHttpWorker.createOutMessage(
                            inMsg,
                            inMsg.getContent(),
                            { status: ResultCode.HTTP_ERROR, message: err },
                        ),
                    );
                }

                if (!response.statusCode || response.statusCode !== 200) {
                    return reject(
                        AHttpWorker.createOutMessage(
                            inMsg,
                            inMsg.getContent(),
                            {
                                status: ResultCode.HTTP_ERROR,
                                message: `Http response with code ${response.statusCode} received`,
                            },
                        ),
                    );
                }

                const outMsg = AHttpWorker.createOutMessage(
                    inMsg,
                    body,
                    { status: ResultCode.SUCCESS, message: "Http worker OK." },
                );

                return resolve(outMsg);
            });
        });
    }

}

export default HttpWorker;
