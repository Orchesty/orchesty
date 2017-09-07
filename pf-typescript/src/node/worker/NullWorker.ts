import logger from "lib-nodejs/dist/src/logger/Logger";
import JobMessage from "../../message/JobMessage";
import {ResultCode} from "../../message/ResultCode";
import IWorker from "./IWorker";

class NullWorker implements IWorker {

    public processData(msg: JobMessage): Promise<JobMessage> {
        msg.setResult({status: ResultCode.SUCCESS, message: "Null worker OK"});

        logger.info(`Worker[type"null"] processed message[id="${msg.getUuid()}]"`);

        return Promise.resolve(msg);
    }

}

export default NullWorker;
