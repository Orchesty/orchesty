import JobMessage from "../../message/JobMessage";
import {ResultCode} from "../../message/ResultCode";
import IWorker from "./IWorker";

/**
 * Worker for testing purposes,
 */
class UppercaseWorker implements IWorker {

    /**
     *
     * @param {JobMessage} msg
     * @return {Promise<JobMessage[]>}
     */
    public processData(msg: JobMessage): Promise<JobMessage[]> {
        msg.setContent(msg.getContent().toUpperCase());
        msg.setResult({code: ResultCode.SUCCESS, message: "Uppercase worker OK"});

        return Promise.resolve([msg]);
    }

    /**
     * Returns whether the worker is ready or not
     *
     * @return {Promise<boolean>}
     */
    public isWorkerReady(): Promise<boolean> {
        return Promise.resolve(true);
    }

}

export default UppercaseWorker;
