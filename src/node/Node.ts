import * as express from "express";
import IMetrics from "lib-nodejs/dist/src/metrics/IMetrics";
import logger from "../logger/Logger";
import JobMessage from "../message/JobMessage";
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

    private id: string;
    private drain: IDrain;
    private faucet: IFaucet;
    private worker: IWorker;
    private debugPort: number;
    private isInitial: boolean;
    private nodeStatus: NODE_STATUS;
    private metrics: IMetrics;

    constructor(
        id: string,
        worker: IWorker,
        faucet: IFaucet,
        drain: IDrain,
        debugPort: number,
        metrics: IMetrics,
        isInitial: boolean = false,
    ) {
        this.id = id;
        this.worker = worker;
        this.faucet = faucet;
        this.drain = drain;
        this.debugPort = debugPort;
        this.isInitial = isInitial;

        this.nodeStatus = NODE_STATUS.BRIDGE_NOT_READY;
        this.metrics = metrics;
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
            app.listen(this.debugPort, () => {
                logger.info(`Node provides ${ROUTE_STATUS} on:${this.debugPort}`, { node_id: this.id });
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

        const processFn = (msgIn: JobMessage): Promise<JobMessage> => {
            logger.info(`Message received.`, logger.ctxFromMsg(msgIn));

            return this.worker.processData(msgIn)
                .then((msgOut: JobMessage) => {
                    this.sendProcessDurationMetric(msgOut);
                    this.drain.forward(msgOut);

                    return msgIn;
                })
                .catch((err: any) => {
                    logger.error(`Node process failed.`, logger.ctxFromMsg(msgIn, err));

                    return msgIn;
                });
        };

        return this.faucet.open(processFn)
            .then(() => {
                logger.info("Faucet has been opened.", {node_id: this.id});
            });
    }

    /**
     *
     * @param {JobMessage} msg
     */
    private sendProcessDurationMetric(msg: JobMessage): void {
        logger.info(
            `Node worker result["status="${msg.getResult().code}", message="${msg.getResult().message}". \
            process_duration="${msg.getProcessDuration()}"].`,
            logger.ctxFromMsg(msg),
        );

        this.metrics.send({node_process_duration: msg.getProcessDuration()})
            .catch((err) => {
                logger.warn("Unable to send metrics", logger.ctxFromMsg(msg, err));
            });
    }

}

export default Node;
