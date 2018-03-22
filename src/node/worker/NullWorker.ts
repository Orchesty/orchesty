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
        logger.info(`Worker[type="null"] is processing message. Headers: ${JSON.stringify(msg.getHeaders().getRaw())}`);

        // msg.setResult({code: ResultCode.SUCCESS, message: "Null worker passed message."});

        // Add some custom logic here for ad-hoc testing
        if (this.settings && this.settings.node_label.node_name.toLowerCase() === "debug") {
            const data = JSON.parse(msg.getContent());

            // fake splitter
            if (data.bids && data.asks) {
                if (Math.random() >= 0.5) {
                    delete data.bids;
                } else {
                    delete data.asks;
                }

                data.split = true;

                msg.setResult({code: ResultCode.SUCCESS, message: "Fake splitter OK."});
                msg.setContent(JSON.stringify(data));

                return Promise.resolve([msg]);
            }

            // fake filter
            if (data.split && data.split === true) {
                const id = this.settings.node_label.node_id;
                const lastChar = id.substr(id.length - 1);

                if (parseInt(lastChar, 10) % 2 === 0) {
                    if (data.bids) {
                        msg.setContent("All ok");
                        msg.setResult({code: ResultCode.SUCCESS, message: "Bids filter OK."});
                        msg.setContent(JSON.stringify(data));
                    } else {
                        msg.setResult({code: ResultCode.DO_NOT_CONTINUE, message: "No bids in data."});
                        msg.setContent(JSON.stringify(data));
                    }
                } else {
                    if (data.asks) {
                        msg.setContent("All ok");
                        msg.setResult({code: ResultCode.SUCCESS, message: "Asks filter OK."});
                        msg.setContent(JSON.stringify(data));
                    } else {
                        msg.setResult({code: ResultCode.DO_NOT_CONTINUE, message: "No asks in data."});
                        msg.setContent(JSON.stringify(data));
                    }
                }

                return Promise.resolve([msg]);
            }

        } else {
            msg.setResult({code: ResultCode.SUCCESS, message: "Null worker passed message."});
        }

        return Promise.resolve([msg]);
    }

    /**
     * Returns whether the worker is ready or not
     *
     * @return {Promise<boolean>}
     */
    public isWorkerReady(): Promise<boolean> {
        logger.info(`Worker[type="null"] isWorkerReady() called. Responding with true.`);

        return Promise.resolve(true);
    }

}

export default NullWorker;
