import JobMessage from "../../message/JobMessage";
import {ResultCode} from "../../message/ResultCode";
import IWorker from "./IWorker";

class UppercaseWorker implements IWorker {

    public processData(msg: JobMessage): Promise<JobMessage> {
        msg.setContent(msg.getContent().toUpperCase());
        msg.setResult({status: ResultCode.SUCCESS, message: "Uppercase worker OK"});

        return Promise.resolve(msg);
    }

}

export default UppercaseWorker;
