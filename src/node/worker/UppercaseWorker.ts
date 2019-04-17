import JobMessage from "../../message/JobMessage";
import {ResultCode} from "../../message/ResultCode";
import AWorker from "./AWorker";

/**
 * Worker for testing purposes,
 */
class UppercaseWorker extends AWorker {

    /**
     * Converts the whole message content to uppercase
     *
     * @inheritdoc
     */
    public processData(msg: JobMessage): Promise<JobMessage[]> {
        msg.setContent(msg.getContent().toUpperCase());
        msg.setResult({code: ResultCode.SUCCESS, message: "Uppercase worker OK"});

        return Promise.resolve([msg]);
    }

    /** @inheritdoc */
    public async isWorkerReady(): Promise<boolean> {
        return true;
    }

}

export default UppercaseWorker;
