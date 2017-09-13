import * as bodyParser from "body-parser";
import * as express from "express";
import logger from "lib-nodejs/dist/src/logger/Logger";
import JobMessage from "../../message/JobMessage";
import IFaucet, {FaucetProcessMsgFn} from "./IFaucet";

export interface IValidHttpRequest {
    headers: {
        job_id: string,
        sequence_id: string,
    };
    body: {
        data: any,
        settings: {},
    };
}

export interface IHttpFaucetSettings {
    port: number;
}

class HttpFaucet implements IFaucet {

    private port: number;
    private path: string = "/";

    /**
     *
     * @param {IHttpFaucetSettings} settings
     */
    constructor(settings: IHttpFaucetSettings) {
        this.port = settings.port;
    }

    /**
     *
     * @param {FaucetProcessMsgFn} processFn
     * @return {Promise<void>}
     */
    public open(processFn: FaucetProcessMsgFn): Promise<void> {
        const app = express();
        app.use(bodyParser.json());

        app.post(this.path, (req: any, resp: any) => {
            this.handleRequest(req, processFn)
                .then(() => {
                    resp.sendStatus(200);
                })
                .catch((err: Error) => {
                    logger.error(`HttpFaucet processData error: ${err}`);
                    resp.status(500).end(err.message);
                });
        });

        const server = app.listen(this.port, () => {
            logger.debug(`HttpFaucet ready. Listening on: \\
                ${server.address().address}:${server.address().port}${this.path}`);
        });

        return Promise.resolve();
    }

    /**
     *
     * @param {IValidHttpRequest} req
     * @param {FaucetProcessMsgFn} processData
     * @return {Promise<void>}
     */
    private handleRequest(req: IValidHttpRequest, processData: FaucetProcessMsgFn): Promise<JobMessage> {
        let inMsg: JobMessage;

        try {
            const body = JSON.stringify(req.body);
            inMsg = new JobMessage(req.headers.job_id, parseInt(req.headers.sequence_id, 10), req.headers, body);
        } catch (err) {
            err.message = `Cannot create JobMessage from http request. ${err.message}`;
            return Promise.reject(err);
        }

        return processData(inMsg);
    }

}

export default HttpFaucet;
