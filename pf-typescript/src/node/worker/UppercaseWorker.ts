import logger from "lib-nodejs/dist/src/logger/Logger";
import JobMessage from "../../message/JobMessage";
import {ResultCode} from "../../message/ResultCode";
import IWorker from "./IWorker";

class UppercaseWorker implements IWorker {

    public processData(msg: JobMessage): Promise<JobMessage[]> {
        msg.setContent(msg.getContent().toUpperCase());
        msg.setResult({status: ResultCode.SUCCESS, message: "Uppercase worker OK"});

        logger.info(`Worker[type"uppercase"] processed message[id="${msg.getUuid()}]"`);

        const out: JobMessage[] = [];
        out.push(msg);

        return Promise.resolve(out);
    }

}

export default UppercaseWorker;
