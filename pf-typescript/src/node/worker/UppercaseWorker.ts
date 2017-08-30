import logger from "lib-nodejs/dist/src/logger/Logger";
import JobMessage from "../../message/JobMessage";
import IWorker from "./IWorker";

class UppercaseWorker implements IWorker {

    public processData(msg: JobMessage): Promise<JobMessage> {
        const original = msg.open();
        msg.setContent(JSON.stringify({ data: original.data.toUpperCase(), settings: original.settings }));
        msg.setJobResultOK();

        logger.info(`UppercaseWorker changed data to: "${msg.getContent()}"`);

        return Promise.resolve(msg);
    }

}

export default UppercaseWorker;
