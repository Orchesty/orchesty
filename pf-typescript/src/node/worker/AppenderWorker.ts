import logger from "lib-nodejs/dist/src/logger/Logger";
import JobMessage from "../../message/JobMessage";
import {ResultCode} from "../../message/ResultCode";
import IWorker from "./IWorker";

export interface IAppenderWorkerSettings {
    suffix: string;
}

class AppenderWorker implements IWorker {

    constructor(private settings: IAppenderWorkerSettings) {}

    public processData(msg: JobMessage): Promise<JobMessage[]> {
        msg.setContent(`${msg.getContent()}${this.settings.suffix}`);
        msg.setResult({status: ResultCode.SUCCESS, message: "Appender worker OK"});

        logger.info(`Worker[type"appender"] processed message[id="${msg.getUuid()}]"`);

        const out: JobMessage[] = [];
        out.push(msg);

        return Promise.resolve(out);
    }

}

export default AppenderWorker;
