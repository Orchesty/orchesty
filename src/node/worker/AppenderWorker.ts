import JobMessage from "../../message/JobMessage";
import {ResultCode} from "../../message/ResultCode";
import {INodeLabel} from "../../topology/Configurator";
import AWorker from "./AWorker";

export interface IAppenderWorkerSettings {
    node_label: INodeLabel;
    suffix: string;
}

/**
 * Worker for testing purposes,
 */
class AppenderWorker extends AWorker {

    /**
     *
     * @param {IAppenderWorkerSettings} settings
     */
    constructor(private settings: IAppenderWorkerSettings) {
        super();
    }

     /**
      * Appends suffix from config to the end of message body.
      *
      * @inheritdoc
      */
    public processData(msg: JobMessage): Promise<JobMessage[]> {
        msg.setContent(`${msg.getContent()}${this.settings.suffix}`);
        msg.setResult({code: ResultCode.SUCCESS, message: "Appender worker OK"});

        return Promise.resolve([msg]);
    }

    /** @inheritdoc */
    public isWorkerReady(): Promise<boolean> {
        return Promise.resolve(true);
    }

}

export default AppenderWorker;
