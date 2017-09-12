import * as express from "express";
import logger from "lib-nodejs/dist/src/logger/Logger";
import Metrics from "lib-nodejs/dist/src/metrics/Metrics";
import {metricsOptions} from "../config";
import JobMessage from "../message/JobMessage";
import IDrain from "./drain/IDrain";
import IFaucet from "./faucet/IFaucet";
import IWorker from "./worker/IWorker";

export enum NODE_STATUS {
    READY = 200,
    UNPREPARED = 503, // Service Unavailable
    ERROR = 500, // Internal Server Error
}

const ROUTE_STATUS = "/status";
const ROUTE_OPEN = "/open";

const emptyFn: () => void = () => {
    // function that does nothing and serves as a mock
};

/**
 * Node class wraps faucet-worker-drain objects and links them together
 * Also is responsible for sending basic metrics
 */
class Node {

    private id: string;
    private drain: IDrain;
    private faucet: IFaucet;
    private worker: IWorker;
    private debugPort: number;
    private isInitial: boolean;
    private nodeStatus: NODE_STATUS;
    private metrics: Metrics;

    constructor(
        id: string,
        worker: IWorker,
        faucet: IFaucet,
        drain: IDrain,
        debugPort: number,
        isInitial: boolean = false,
    ) {
        this.id = id;
        this.worker = worker;
        this.faucet = faucet;
        this.drain = drain;
        this.debugPort = debugPort;
        this.isInitial = isInitial;

        this.nodeStatus = NODE_STATUS.UNPREPARED;
        this.metrics = new Metrics(metricsOptions.measurement, id, id, metricsOptions.server, metricsOptions.port);
    }

    /**
     * Starts node's http server
     *  1. provides self-status
     *  2. accepts signal to start itself in case of first node in topology
     *
     * @private
     */
    public startServer(): Promise<void> {
        const app = express();

        // All nodes have "/status" route to indicate their readiness
        app.get(ROUTE_STATUS, (req, resp) => {
            resp.sendStatus(this.nodeStatus);
        });

        return new Promise((resolve) => {
            const server = app.listen(this.debugPort, () => {
                const sa = server.address();
                logger.debug(`Node '${this.id}' provides "${ROUTE_STATUS}" on: ${sa.address}:${sa.port}`);
                if (this.isInitial) {
                    logger.debug(`Node '${this.id}' provides "${ROUTE_OPEN}" on: ${sa.address}:${sa.port}`);
                }
                resolve();
            });
        });
    }

    /**
     * Opens node for work
     *
     * @return {Promise}
     * @private
     */
    public open(): Promise<void> {
        this.nodeStatus = NODE_STATUS.READY;

        const processFn = (msgIn: JobMessage) => {
            logger.info(`Node[id=${this.id}] received message[id=${msgIn.getUuid()}].`);

            return this.worker.processData(msgIn)
                .then((msgOut: JobMessage) => {
                    this.sendProcessDurationMetric(msgOut);

                    return this.drain.forward(msgOut);
                })
                .then((forwarded: JobMessage) => {
                    this.sendTotalDurationMetric(forwarded);

                    return forwarded;
                });
        };

        return this.faucet.open(processFn)
            .then(() => {
                logger.info(`Node[id=${this.id}] faucet has been opened.`);
            });
    }

    /**
     *
     * @param {JobMessage} msg
     */
    private sendProcessDurationMetric(msg: JobMessage): void {
        logger.info(
            `Node[id=${this.id}] received processed message[id="${msg.getUuid()}", \
            status="${msg.getResult().status}", info="${msg.getResult().message}". \
            process_duration="${msg.getProcessDuration()}"].`,
        );

        this.metrics.send({node_process_duration: msg.getProcessDuration()})
            .catch((err) => {
                logger.warn(err);
            });
    }

    /**
     *
     * @param {JobMessage} msg
     */
    private sendTotalDurationMetric(msg: JobMessage): void {
        this.metrics.send({node_total_duration: msg.getTotalDuration()})
            .catch((err) => {
                logger.warn(err);
            });
    }

}

export default Node;
