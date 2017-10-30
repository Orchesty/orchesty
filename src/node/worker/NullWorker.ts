import logger from "../../logger/Logger";
import JobMessage from "../../message/JobMessage";
import {ResultCode} from "../../message/ResultCode";
import IWorker from "./IWorker";

class NullWorker implements IWorker {

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
