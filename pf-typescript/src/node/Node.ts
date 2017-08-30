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
    private opened: boolean;

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
        this.opened = false;
    }

    /**
     * Opens all nodes except the first one
     *
     * @return Promise<Function>
     */
    public prepare(): Promise<() => void> {
        if (this.isInitial) {
            this.nodeStatus = NODE_STATUS.READY;

            return Promise.resolve(emptyFn);
        }

        const a = this.openNode();
        return a
            .then(() => {
                this.nodeStatus = NODE_STATUS.READY;
                logger.info("Node opened.");

                return emptyFn;
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

        // First node has "/open" node to start consuming from source
        if (this.isInitial) {
            app.get(ROUTE_OPEN, (req, resp) => {
                logger.info("Open request received.");
                this.openNode()
                    .then((run: () => void) => {
                        resp.sendStatus(200);
                        run();
                    })
                    .catch(() => {
                        resp.sendStatus(NODE_STATUS.ERROR);
                    });
            });
        }

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
     *
     * @return {boolean}
     */
    public isOpened() {
        return this.opened;
    }

    /**
     * Opens node for work
     *
     * @return {Promise}
     * @private
     */
    private openNode(): Promise<() => void> {
        if (this.opened) {
            return Promise.resolve(emptyFn);
        }

        this.opened = true;

        return this.faucet.open(
            (msgIn: JobMessage) => this.worker.processData(msgIn),
            (msgOut: JobMessage) => this.drain.open(msgOut),
        );
    }

}

export default Node;
