import logger from "lib-nodejs/dist/src/logger/Logger";
import JobMessage from "../../message/JobMessage";
import {ResultCode} from "../../message/ResultCode";
import IWorker from "./IWorker";

class SplitterWorker implements IWorker {

    public processData(msg: JobMessage): Promise<JobMessage[]> {
        msg.setResult({status: ResultCode.SUCCESS, message: "Null worker OK"});

        logger.info(`Worker[type"null"] processed message[id="${msg.getUuid()}]"`);

        // TODO - read the data from message onbject
        // TODO - split them into chunks if data is array
        // TODO - create new JobMessage object for every chunk
        // TODO - return all created JobMessages

        const out: JobMessage[] = [];
        out.push(msg);

        return Promise.resolve(out);
    }

}

export default SplitterWorker;
