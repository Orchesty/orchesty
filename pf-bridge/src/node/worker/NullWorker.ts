import * as http from "http";
import * as request from "request";
import logger from "../../logger/Logger";
import JobMessage from "../../message/JobMessage";
import {ResultCode} from "../../message/ResultCode";
import IWorker from "./IWorker";

class NullWorker implements IWorker {

    private agent: http.Agent;

    constructor() {
        this.agent = new http.Agent({ keepAlive: true, maxSockets: Infinity });
    }

    /**
     * Does not modify message, just marks it as processed
     *
     * @param {JobMessage} msg
     * @return {Promise<JobMessage[]>}
     */
    public processData(msg: JobMessage): Promise<JobMessage[]> {
        logger.info(`Worker[type="null"] is processing message. Headers: ${JSON.stringify(msg.getHeaders().getRaw())}. \
            Content: ${msg.getContent()}`, logger.ctxFromMsg(msg));

        msg.setResult({code: ResultCode.SUCCESS, message: "Null worker passed message."});

        const opts = {
            url: "http://spitter-api:80/black-hole",
            method: "GET",
            timeout: 10,
            agent: this.agent,
        };

        request(opts, () => {
            // we don't need response
        });

        return Promise.resolve([msg]);
    }

    /**
     * Returns whether the worker is ready or not
     *
     * @return {Promise<boolean>}
     */
    public isWorkerReady(): Promise<boolean> {
        logger.info(`Worker[type="null"] isWorkerReady() called. Responding with true.`);

        return Promise.resolve(true);
    }

}

export default NullWorker;
