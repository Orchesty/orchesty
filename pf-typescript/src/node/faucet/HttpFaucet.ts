import * as bodyParser from "body-parser";
import * as express from "express";
import logger from "lib-nodejs/dist/src/logger/Logger";
import JobMessage from "../../message/JobMessage";
import { DrainOpenFn } from "../drain/IDrain";
import { WorkerProcessFn } from "../worker/IWorker";
import IFaucet from "./IFaucet";

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
     * @param {WorkerProcessFn} processFn
     * @param {DrainOpenFn} drainFn
     * @return {Promise<() => void>}
     */
    public open(processFn: WorkerProcessFn, drainFn: DrainOpenFn): Promise<() => void> {
        const app = express();
        app.use(bodyParser.json());

        app.post(this.path, (req: any, resp: any) => {
            this.handleRequest(req, processFn, drainFn)
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

        return Promise.resolve(() => {
            // do nothing, already prepared
        });
    }

    /**
     *
     * @param {IValidHttpRequest} req
     * @param {WorkerProcessFn} processData
     * @param {DrainOpenFn} drain
     * @return {Promise<void>}
     */
    private handleRequest(req: IValidHttpRequest, processData: WorkerProcessFn, drain: DrainOpenFn): Promise<void> {
        const body = JSON.stringify(req.body);
        const inMsg = new JobMessage(req.headers, body);

        return processData(inMsg)
            .then((outMsg: JobMessage) => {
                // Yes, ignore drain promise, do not wait till the end of it
                drain(outMsg);
            });
    }

}

export default HttpFaucet;
