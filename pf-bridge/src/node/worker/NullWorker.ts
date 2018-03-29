import * as http from "http";
import logger from "../../logger/Logger";
import JobMessage from "../../message/JobMessage";
import {ResultCode} from "../../message/ResultCode";
import {INodeLabel} from "../../topology/Configurator";
import IWorker from "./IWorker";

export interface INullWorkerSettings {
    node_label: INodeLabel;
}

class NullWorker implements IWorker {

    private agent: http.Agent;

    constructor(private settings: INullWorkerSettings) {
        this.agent = new http.Agent({ keepAlive: true, maxSockets: Infinity });
    }

    /**
     * Does not modify message, just marks it as processed
     *
     * @param {JobMessage} msg
     * @return {Promise<JobMessage[]>}
     */
    public processData(msg: JobMessage): Promise<JobMessage[]> {
        logger.debug(
            `Worker[type="null"] processing message.`,
            {
                correlation_id: msg.getCorrelationId(),
                node_id: this.settings.node_label.node_id,
                node_name: this.settings.node_label.node_name,
                topology_id: this.settings.node_label.topology_id,
                data : JSON.stringify(msg.getHeaders().getRaw()),
            },
        );

        msg.setResult({code: ResultCode.SUCCESS, message: "Null worker passed message."});

        if (this.settings && this.settings.node_label.node_name.toLowerCase() === "debug") {
            // Add some custom logic here for ad-hoc testing
            msg.setResult({code: ResultCode.SUCCESS, message: "Debug worker passed message."});
        }

        return Promise.resolve([msg]);
    }

    /**
     * Returns whether the worker is ready or not
     *
     * @return {Promise<boolean>}
     */
    public isWorkerReady(): Promise<boolean> {
        logger.debug(`Worker[type="null"] isWorkerReady() called. Responding with true.`);

        return Promise.resolve(true);
    }

}

export default NullWorker;
