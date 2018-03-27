import * as express from "express";
import {IMetrics} from "metrics-sender/dist/lib/metrics/IMetrics";
import IStoppable from "../IStoppable";
import logger from "../logger/Logger";
import JobMessage from "../message/JobMessage";
import {ResultCode, ResultCodeGroup} from "../message/ResultCode";
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
class Node implements IStoppable {

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

        const processFn = async (msgIn: JobMessage): Promise<void> => {
            try {
                msgIn.getMeasurement().markWorkerStart();
                const msgsOut = await this.worker.processData(msgIn);

                msgsOut.forEach((msgOut: JobMessage) => {
                    msgOut.getMeasurement().markWorkerEnd();

                    // send to following bridge here
                    this.drain.forward(msgOut);

                    msgOut.getMeasurement().markFinished();
                    this.sendBridgeMetrics(msgOut);
                });
            } catch (err) {
                logger.error(`Node process failed.`, logger.ctxFromMsg(msgIn, err));
            }
        };

        return this.faucet.open(processFn)
            .then(() => {
                logger.debug("Faucet has been opened.", {node_id: this.id});
            });
    }

    /**
     * TODO - safely close worker, drain and http server as well
     *
     * Stops all node's services
     *
     * @return {Promise<void>}
     */
    public async stop(): Promise<void> {
        // Stop faucet and have and keep a safe time period for opened jobs
        await Promise.all([
            this.faucet.stop(),
            new Promise((resolve) => setTimeout(resolve, 2000)),
        ]);
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
    private async sendBridgeMetrics(msg: JobMessage): Promise<void> {
        try {
            const isSuccess = msg.getResult().code === ResultCode.SUCCESS ||
                msg.getResultGroup() === ResultCodeGroup.NON_STANDARD;

            const measurements = {
                bridge_job_waiting_duration: msg.getMeasurement().getWaitingDuration(),
                bridge_job_worker_duration: msg.getMeasurement().getWorkerDuration(),
                bridge_job_total_duration: msg.getMeasurement().getNodeTotalDuration(),
                bridge_job_result_success: isSuccess ? "true" : "false",
            };

            this.metrics.addTag("node_id", msg.getNodeLabel().node_id);

            logger.debug(`Sending metrics: ${JSON.stringify(measurements)}`, logger.ctxFromMsg(msg));
            await this.metrics.send(measurements, false);
        } catch (err) {
            logger.warn("Unable to send metrics", logger.ctxFromMsg(msg, err));
        }
    }

}

export default Node;
