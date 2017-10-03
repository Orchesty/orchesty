import JobMessage from "../../message/JobMessage";
import {ResultCode} from "../../message/ResultCode";
import IWorker from "./IWorker";

export interface IAppenderWorkerSettings {
    suffix: string;
}

/**
 * Worker for testing purposes,
 */
class AppenderWorker implements IWorker {

    constructor(private settings: IAppenderWorkerSettings) {}

    /**
     * Appends string given message content
     *
     * @param {JobMessage} msg
     * @return {Promise<JobMessage>}
     */
    public processData(msg: JobMessage): Promise<JobMessage> {
        msg.setContent(`${msg.getContent()}${this.settings.suffix}`);
        msg.setResult({code: ResultCode.SUCCESS, message: "Appender worker OK"});

        return Promise.resolve(msg);
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

export default AppenderWorker;
