import * as http from "http";
import logger from "../../logger/Logger";
import JobMessage from "../../message/JobMessage";
import {ResultCode} from "../../message/ResultCode";
import {INodeLabel} from "../../topology/Configurator";
import AWorker from "./AWorker";

export interface INullWorkerSettings {
    node_label: INodeLabel;
}

class NullWorker extends AWorker {

    private agent: http.Agent;

    /**
     *
     * @param {INullWorkerSettings} settings
     */
    constructor(private settings: INullWorkerSettings) {
        super();

        this.agent = new http.Agent({ keepAlive: true, maxSockets: Infinity });
    }

    /**
     * Does not modify message, just marks it as processed
     *
     * @inheritdoc
     */
    public async processData(msg: JobMessage): Promise<JobMessage[]> {
        // add special header with next nods
        if (this.additionalHeaders !== undefined) {
            this.additionalHeaders.forEach((value: string, key: string) => {
                msg.getHeaders().setPFHeader(key, value);
            });
        }

        if (this.settings && this.settings.node_label.node_name.toLowerCase() === "debug") {
            msg.setResult({code: ResultCode.SUCCESS, message: "Debug worker passed message."});

            logger.info(
                `Worker[type="debug"] processing message.`,
                {
                    correlation_id: msg.getCorrelationId(),
                    node_id: this.settings.node_label.node_id,
                    node_name: this.settings.node_label.node_name,
                    topology_id: this.settings.node_label.topology_id,
                    data : JSON.stringify({headers: msg.getHeaders().getRaw(), data: msg.getContent()}),
                },
            );
        } else {
            msg.setResult({code: ResultCode.SUCCESS, message: "Null worker passed message."});

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
        }

        return [msg];
    }

    /** @inheritdoc */
    public async isWorkerReady(): Promise<boolean> {
        logger.debug(`Worker[type="null"] isWorkerReady() called. Responding with true.`);

        return true;
    }

}

export default NullWorker;
