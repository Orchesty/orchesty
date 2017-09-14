import JobMessage from "../../message/JobMessage";
import {ResultCode} from "../../message/ResultCode";
import IWorker from "./IWorker";

export interface IAppenderWorkerSettings {
    suffix: string;
}

class AppenderWorker implements IWorker {

    constructor(private settings: IAppenderWorkerSettings) {}

    public processData(msg: JobMessage): Promise<JobMessage> {
        msg.setContent(`${msg.getContent()}${this.settings.suffix}`);
        msg.setResult({status: ResultCode.SUCCESS, message: "Appender worker OK"});

        return Promise.resolve(msg);
    }

}

export default AppenderWorker;
