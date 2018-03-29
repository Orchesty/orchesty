import logger from "../../logger/Logger";
import JobMessage from "../../message/JobMessage";
import {ResultCode} from "../../message/ResultCode";
import {INodeLabel} from "../../topology/Configurator";
import Resequencer from "../Resequencer";
import AWorker from "./AWorker";

export interface IResequencerWorkerSettings {
    node_label: INodeLabel;
}

class ResequencerWorker extends AWorker {

    private resequencer: Resequencer;

    /**
     *
     * @param {IResequencerWorkerSettings} settings
     */
    constructor(private settings: IResequencerWorkerSettings) {
        super();
        this.resequencer = new Resequencer(settings.node_label.id);
    }

    /**
     * Does not modify message, just marks it as processed
     *
     * @inheritdoc
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

    /** @inheritdoc */
    public isWorkerReady(): Promise<boolean> {
        logger.debug(`Worker[type="resequencer"] isWorkerReady() called. Responding with true.`);

        return Promise.resolve(true);
    }

}

export default ResequencerWorker;
