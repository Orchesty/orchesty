import logger from "lib-nodejs/dist/src/logger/Logger";
import JobMessage from "../../message/JobMessage";
import {ResultCode} from "../../message/ResultCode";
import IWorker from "./IWorker";

export interface IAppenderWorkerSettings {
    suffix: string;
}

class AppenderWorker implements IWorker {

    constructor(private settings: IAppenderWorkerSettings) {}

    public processData(inMsg: JobMessage): Promise<JobMessage> {
        const outMsg = new JobMessage(
            inMsg.getJobId(),
            inMsg.getSequenceId(),
            inMsg.getHeaders(),
            `${inMsg.getContent()}${this.settings.suffix}`,
            {
                status: ResultCode.SUCCESS,
                message: "Appender worker process message successfully",
            },
        );

        logger.info(`AppenderWorker changed data to: "${outMsg.getContent()}"`);

        return Promise.resolve(outMsg);
    }

}

export default AppenderWorker;
