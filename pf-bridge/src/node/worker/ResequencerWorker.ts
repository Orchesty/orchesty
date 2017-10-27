import JobMessage from "../../message/JobMessage";
import {INodeLabel} from "../../topology/Configurator";
import Resequencer from "../Resequencer";
import IWorker from "./IWorker";

export interface IResequencerWorkerSettings {
    node_label: INodeLabel;
}

class ResequencerWorker implements IWorker {

    private resequencer: Resequencer;

    constructor(private settings: IResequencerWorkerSettings) {
        this.resequencer = new Resequencer(settings.node_label.id);
    }

    /**
     * Does not modify message, just marks it as processed
     *
     * @param {JobMessage} msg
     * @return {Promise<JobMessage>}
     */
    public processData(msg: JobMessage): Promise<JobMessage> {
        const buffered = this.resequencer.getMessages(msg);

        // TODO - allow return multiple messages (or create special resequencerDrain)?

        return Promise.resolve(msg);
    }

    /**
     * Returns whether the worker is ready or not
     *
     * @return {Promise<boolean>}
     */
    public isWorkerReady(): Promise<boolean> {
        return Promise.resolve(true);
    }

}

export default ResequencerWorker;
