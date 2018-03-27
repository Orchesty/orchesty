import logger from "../../logger/Logger";
import JobMessage from "../../message/JobMessage";
import {ResultCode} from "../../message/ResultCode";
import {INodeLabel} from "../../topology/Configurator";
import Resequencer from "../Resequencer";
import IWorker from "./IWorker";

export interface IResequencerWorkerSettings {
    node_label: INodeLabel;
}

class ResequencerWorker implements IWorker {

    private resequencer: Resequencer;

    /**
     *
     * @param {IResequencerWorkerSettings} settings
     */
    constructor(private settings: IResequencerWorkerSettings) {
        this.resequencer = new Resequencer(settings.node_label.id);
    }

    /**
     * Does not modify message, just marks it as processed
     *
     * @param {JobMessage} msg
     * @return {Promise<JobMessage>}
     */
    public processData(msg: JobMessage): Promise<JobMessage[]> {
        const sId = msg.getSequenceId();
        const waitingFor = this.resequencer.getWaitingForSequenceId(msg.getProcessId());
        logger.debug(`Worker[type=resequencer] accepted message with sequenceId="${sId} \
            while waiting for sequenceId="${waitingFor}"`, logger.ctxFromMsg(msg));

        const bufferedMessages = this.resequencer.getMessages(msg);

        bufferedMessages.forEach((buf: JobMessage) => {
            buf.setResult({
                code: ResultCode.SUCCESS,
                message: "Resequencing successful.",
            });
        });

        return Promise.resolve(bufferedMessages);
    }

    /**
     * Returns whether the worker is ready or not
     *
     * @return {Promise<boolean>}
     */
    public isWorkerReady(): Promise<boolean> {
        logger.debug(`Worker[type="resequencer"] isWorkerReady() called. Responding with true.`);

        return Promise.resolve(true);
    }

}

export default ResequencerWorker;
