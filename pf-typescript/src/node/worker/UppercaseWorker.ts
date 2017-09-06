import logger from "lib-nodejs/dist/src/logger/Logger";
import JobMessage from "../../message/JobMessage";
import {ResultCode} from "../../message/ResultCode";
import IWorker from "./IWorker";

class UppercaseWorker implements IWorker {

    public processData(inMsg: JobMessage): Promise<JobMessage> {
        const outMsg = new JobMessage(
            inMsg.getJobId(),
            inMsg.getSequenceId(),
            inMsg.getHeaders(),
            inMsg.getContent().toUpperCase(),
            {
                status: ResultCode.SUCCESS,
                message: "Uppercase worker process message successfully",
            },
        );

        logger.info(`UppercaseWorker changed data to: "${outMsg.getContent()}"`);

        return Promise.resolve(outMsg);
    }

}

export default UppercaseWorker;
