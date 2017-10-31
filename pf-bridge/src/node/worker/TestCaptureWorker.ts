import JobMessage from "../../message/JobMessage";
import {ResultCode} from "../../message/ResultCode";
import IWorker from "./IWorker";

/**
 * Worker for testing purposes.
 *
 * It provides method for retrieving all messages passed to processData function
 */
class TestCaptureWorker implements IWorker {

    private captured: Array<{body: string, headers: {}}> = [];

    /**
     * Returns message as it would be processed, but captures given message
     *
     * @param {JobMessage} msg
     * @return {Promise<JobMessage[]>}
     */
    public processData(msg: JobMessage): Promise<JobMessage[]> {
        this.captured.push({body: msg.getContent(), headers: msg.getHeaders().getRaw()});

        msg.setResult({code: ResultCode.SUCCESS, message: "Test worker OK"});

        return Promise.resolve([msg]);
    }

    /**
     * Returns whether the worker is ready or not
     *
     * @return {Promise<boolean>}
     */
    public async isWorkerReady(): Promise<boolean> {
        return true;
    }

    /**
     * Clears the array of captured messages
     */
    public clearCaptured() {
        this.captured = [];
    }

    /**
     * Waits for timeout period and returns the captured messages
     *
     * @param {number} timeout
     * @return {Promise<any>}
     */
    public getCaptured(timeout: number = 0) {
        return new Promise((resolve) => {
            setTimeout(() => {
                return resolve(this.captured);
            }, timeout);
        });
    }

}

export default TestCaptureWorker;
