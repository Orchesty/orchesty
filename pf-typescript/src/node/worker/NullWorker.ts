import logger from "lib-nodejs/dist/src/logger/Logger";
import JobMessage from "../../message/JobMessage";
import {ResultCode} from "../../message/ResultCode";
import IWorker from "./IWorker";

class NullWorker implements IWorker {

    public processData(inMsg: JobMessage): Promise<JobMessage> {
        const outMsg = new JobMessage(
            inMsg.getJobId(),
            inMsg.getSequenceId(),
            inMsg.getHeaders(),
            inMsg.getContent(),
            {
                status: ResultCode.SUCCESS,
                message: "Null worker process message successfully",
            },
        );

        logger.info(`Worker[type"null"] processed message[id="${inMsg.getUuid()}]"`);

        return Promise.resolve(outMsg);
    }

}

export default NullWorker;
