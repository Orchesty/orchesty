import * as bodyParser from "body-parser";
import * as express from "express";
import logger from "../../logger/Logger";
import JobMessage from "../../message/JobMessage";
import IFaucet, {FaucetProcessMsgFn} from "./IFaucet";
import {INodeLabel} from "../../topology/Configurator";

export interface IValidHttpRequest {
    headers: {
        correlation_id: string,
        process_id: string,
        parent_id: string,
        sequence_id: string,
    };
    body: {
        data: any,
        settings: {},
    };
}

export interface IHttpFaucetSettings {
    node_label: INodeLabel;
    port: number;
}

class HttpFaucet implements IFaucet {

    private port: number;
    private path: string = "/";

    /**
     *
     * @param {IHttpFaucetSettings} settings
     */
    constructor(private settings: IHttpFaucetSettings) {
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
                    logger.error("HttpFaucet processData error.", { node_id: this.settings.node_label.id, error: err});
                    resp.status(500).end(err.message);
                });
        });

        app.listen(this.port, () => {
            logger.info(`HttpFaucet Listening on: ${this.port}${this.path}`, { node_id: this.settings.node_label.id});
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
            inMsg = new JobMessage(
                this.settings.node_label.id,
                req.headers.correlation_id,
                req.headers.process_id,
                req.headers.parent_id,
                parseInt(req.headers.sequence_id, 10),
                req.headers,
                new Buffer(JSON.stringify(req.body)),
            );
        } catch (err) {
            err.message = `Cannot create JobMessage from http request. ${err.message}`;
            return Promise.reject(err);
        }

        return processData(inMsg);
    }

}

export default HttpFaucet;
