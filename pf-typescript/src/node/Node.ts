import * as express from "express";
import logger from "lib-nodejs/dist/src/logger/Logger";
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

class Node {

    private id: string;
    private drain: IDrain;
    private faucet: IFaucet;
    private worker: IWorker;
    private debugPort: number;
    private isInitial: boolean;
    private nodeStatus: NODE_STATUS;

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
    }

    /**
     * Opens all nodes except the first one
     *
     * @return Promise<Function>
     */
    public prepare(): Promise<() => void> {
        return this.openNode()
            .then((run: () => void) => {
                this.nodeStatus = NODE_STATUS.READY;
                logger.info("Node opened.");

                return run;
            });
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
    private openNode(): Promise<() => void> {
        // TODO - add sending basic metrics here

        return this.faucet.open(
            (msgIn: JobMessage) => {
                logger.info(`Node ${this.id} received message.`);
                return this.worker.processData(msgIn)
                    .then((msgOut: JobMessage) => {
                        logger.info(`Node ${this.id} processed message`);
                        return msgOut;
                    });
            },
            (msgOut: JobMessage) => {
                return this.drain.open(msgOut)
                    .then((forwarded) => {
                        logger.info(`Node ${this.id} forwarded message.`);
                        return forwarded;
                    });
            },
        );
    }

}

export default Node;
