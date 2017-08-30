import logger from "lib-nodejs/dist/src/logger/Logger";
import JobMessage from "../../message/JobMessage";
import IWorker from "./IWorker";

export interface IAppenderWorkerSettings {
    suffix: string;
}

class AppenderWorker implements IWorker {

    constructor(private settings: IAppenderWorkerSettings) {
    }

    public processData(msg: JobMessage): Promise<JobMessage> {
        const original = msg.open();
        msg.setContent(JSON.stringify(
            { data: `${original.data}${this.settings.suffix}`, settings: original.settings }),
        );
        msg.setJobResultOK();

        logger.info(`AppenderWorker changed data to: "${msg.getContent()}"`);

        return Promise.resolve(msg);
    }

}

export default AppenderWorker;
