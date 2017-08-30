import * as bodyParser from "body-parser";
import * as express from "express";
import logger from "lib-nodejs/dist/src/logger/Logger";
import * as request from "request";
import JobMessage from "../../message/JobMessage";
import { ResultCode } from "../../message/ResultCode";
import { default as AHttpWorker, IHttpWorkerRequestParams } from "./http/AHttpWorker";

export interface IHttpAsyncWorkerSettings {
    response: {
        host: string,
        method: string,
        path: string,
        port: number,
    };
}

class HttpAsyncWorker extends AHttpWorker {

    private settings: IHttpAsyncWorkerSettings;
    private pending: { [key: string]: {
        msg: JobMessage,
        resolve: (msg: JobMessage) => {},
        reject: (msg: JobMessage) => {},
    }};

    constructor(method: string, url: string, settings: IHttpAsyncWorkerSettings) {
        super(method, url);

        this.settings = settings;
        this.pending = {};

        // Starts http server for responses
        const server = express();
        server.use(bodyParser.json());
        server.post(settings.response.path, (req, resp) => {
            this.handleResult(req, resp);
        });
        server.listen(settings.response.port);

        const serverUrl = `${settings.response.host}:${settings.response.port}${settings.response.path}`;
        logger.debug(`Local server listening for '${settings.response.method}' on: '${serverUrl}'`);
    }

    /**
     * TODO - add timeout limitation
     *
     * @param {JobMessage} inMsg
     * @return {Promise<JobMessage>}
     */
    public processData(inMsg: JobMessage): Promise<JobMessage> {
        let reqParams = this.getHttpRequestParams(inMsg);
        reqParams = this.addReplyToHeaders(reqParams);

        return new Promise((resolve: any, reject: any) => {
            request(reqParams, (err, response) => {
                if (err) {
                    inMsg.setJobResultFailed(ResultCode.HTTP_ERROR, err);
                    return reject(inMsg);
                }

                if (!response.statusCode || response.statusCode !== 200) {
                    inMsg.setJobResultFailed(
                        ResultCode.HTTP_ERROR,
                        `Http response with code ${response.statusCode} received`,
                    );
                    return reject(inMsg);
                }

                this.pending[inMsg.getId()] = { msg: inMsg, resolve, reject };

                return JobMessage;
            });
        });
    }

    /**
     *
     * @param {IHttpWorkerRequestParams} reqParams
     * @return {IHttpWorkerRequestParams}
     */
    private addReplyToHeaders(reqParams: IHttpWorkerRequestParams): IHttpWorkerRequestParams {
        const respConf = this.settings.response;
        reqParams.headers.replyToUrl = `${respConf.host}:${respConf.port}${respConf.path}`;
        reqParams.headers.replyToMethod = respConf.method;

        return reqParams;
    }

    /**
     * TODO - get rid of "any" type of req and res
     *
     * @param req
     * @param res
     */
    private handleResult(req: any, res: any) {
        if (!req.headers || !req.headers.job_id ||
            !req.headers["content-type"] || req.headers["content-type"] !== "application/json") {
            res.status(400);
            return res.send('Missing correct "job_id" and/or "Content-Type" headers.');
        }

        const jobId = req.headers.job_id;
        if (!this.pending[jobId]) {
            res.status(400);
            return res.send('Invalid "job_id" header');
        }

        const p = this.pending[jobId];
        const message = p.msg;
        delete this.pending[jobId];

        if (!req.body || !req.body.data || !req.body.settings) {
            // TODO - think about concrete failure status for this case instead of UNKNOWN_ERROR
            message.setJobResultFailed(ResultCode.UNKNOWN_ERROR);
            p.reject(message);
            res.status(200);
            return res.send("No data in body. Is it really OK?");
        }

        message.setContent(JSON.stringify(req.body));
        message.setJobResultOK();
        p.resolve(message);
        res.status(200);

        return res.send("ok");
    }

}

export default HttpAsyncWorker;
