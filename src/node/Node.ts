import * as express from "express";
import {IMetrics} from "metrics-sender/dist/lib/metrics/IMetrics";
import logger from "../logger/Logger";
import JobMessage from "../message/JobMessage";
import {ResultCode} from "../message/ResultCode";
import IDrain from "./drain/IDrain";
import IFaucet from "./faucet/IFaucet";
import IWorker from "./worker/IWorker";

export enum NODE_STATUS {
    READY = 200,
    BRIDGE_NOT_READY = 500,
    WORKER_NOT_READY = 503,
}

const ROUTE_STATUS = "/status";

/**
 * Node class wraps faucet-worker-drain objects and links them together
 * Also is responsible for sending basic metrics
 */
class Node {

    private nodeStatus: NODE_STATUS;

    /**
     *
     * @param {string} id
     * @param {IWorker} worker
     * @param {IFaucet} faucet
     * @param {IDrain} drain
     * @param {IMetrics} metrics
     */
    constructor(
        private id: string,
        private worker: IWorker,
        private faucet: IFaucet,
        private drain: IDrain,
        private metrics: IMetrics,
    ) {
        this.nodeStatus = NODE_STATUS.BRIDGE_NOT_READY;
    }

    /**
     * Starts node's http server
     *  1. provides self-status
     *  2. accepts signal to start itself in case of first node in topology
     *
     * @private
     */
    public startServer(port: number): Promise<void> {
        const app = express();

        // All nodes have "/status" route to indicate their readiness
        app.get(ROUTE_STATUS, (req, resp) => {
            if (this.nodeStatus === NODE_STATUS.BRIDGE_NOT_READY) {
                return resp.status(NODE_STATUS.BRIDGE_NOT_READY).send("Bridge not ready yet");
            }

            this.worker.isWorkerReady().then((isReady: boolean) => {
                if (isReady) {
                    return resp.status(NODE_STATUS.READY).send("Bridge and worker are both ready.");
                } else {
                    return resp.status(NODE_STATUS.WORKER_NOT_READY).send("Worker not ready yet");
                }
            });
        });

        return new Promise((resolve) => {
            app.listen(port, () => {
                logger.info(`Node provides ${ROUTE_STATUS} on: ${port}`, { node_id: this.id });
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

        const processFn = (msgIn: JobMessage): Promise<void> => {
            msgIn.getMeasurement().markWorkerStart();

            return this.worker.processData(msgIn)
                .then((msgsOut: JobMessage[]) => {
                    msgsOut.forEach((msgOut: JobMessage) => {
                        msgOut.getMeasurement().markWorkerEnd();
                        this.drain.forward(msgOut);
                        msgOut.getMeasurement().markFinished();
                        this.sendBridgeMetrics(msgOut);
                    });
                })
                .catch((err: any) => {
                    logger.error(`Node process failed.`, logger.ctxFromMsg(msgIn, err));
                });
        };

        return this.faucet.open(processFn)
            .then(() => {
                logger.info("Faucet has been opened.", {node_id: this.id});
            });
    }

    /**
     *
     * @return {IWorker}
     */
    public getWorker(): IWorker {
        return this.worker;
    }

    /**
     *
     * @param {JobMessage} msg
     */
    private sendBridgeMetrics(msg: JobMessage): void {

        const measurements = {
            bridge_job_waiting_duration: msg.getMeasurement().getWaitingDuration(),
            bridge_job_worker_duration: msg.getMeasurement().getWorkerDuration(),
            bridge_job_total_duration: msg.getMeasurement().getNodeTotalDuration(),
            bridge_job_result_error: msg.getResult().code === ResultCode.SUCCESS,
        };

        logger.info(`Sending metrics: ${JSON.stringify(measurements)}`, logger.ctxFromMsg(msg));

        this.metrics.addTag("node_id", msg.getNodeLabel().node_id);

        this.metrics.send(measurements)
            .catch((err) => {
                logger.warn("Unable to send metrics", logger.ctxFromMsg(msg, err));
            });
    }

}

export default Node;
