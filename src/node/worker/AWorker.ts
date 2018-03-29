import JobMessage from "../../message/JobMessage";
import {ResultCode} from "../../message/ResultCode";
import IWorker from "./IWorker";

abstract class AWorker implements IWorker {

    /**
     * Processes service type messages
     *
     * @param {JobMessage} msg
     * @return {JobMessage}
     */
    public async processService(msg: JobMessage): Promise<JobMessage> {
        msg.setResult({code: ResultCode.SUCCESS, message: "Service message passed by."});

        return msg;
    }

    /**
     * Processes process type messages
     *
     * @param {JobMessage} msg
     * @return {Promise<JobMessage[]>}
     */
    public async processData(msg: JobMessage): Promise<JobMessage[]> {
        return [msg];
    }

    /**
     * Returns whether the worker is fully ready
     *
     * @return {Promise<boolean>}
     */
    public async isWorkerReady(): Promise<boolean> {
        return true;
    }

}

export default AWorker;
