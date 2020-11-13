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
        // add special header with next nods
        if (this.additionalHeaders !== undefined) {
            this.additionalHeaders.forEach((value: string, key: string) => {
                msg.getHeaders().setPFHeader(key, value);
            });
        }

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
