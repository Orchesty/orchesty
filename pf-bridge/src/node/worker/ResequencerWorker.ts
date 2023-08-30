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
        // add special header with next nods
        if (this.additionalHeaders !== undefined) {
            this.additionalHeaders.forEach((value: string, key: string) => {
                msg.getHeaders().setPFHeader(key, value);
            });
        }

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
    public async isWorkerReady(): Promise<boolean> {
        logger.debug(`Worker[type="resequencer"] isWorkerReady() called. Responding with true.`);

        return true;
    }

}

export default ResequencerWorker;
