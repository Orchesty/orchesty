import * as express from "express";
import {Request, Response} from "express";
import {Container} from "hb-utils/dist/lib/Container";
import ICounterStorage from "../counter/storage/ICounterStorage";
import logger from "../logger/Logger";
import Headers from "../message/Headers";
import MultiProbeConnector from "../probe/MultiProbeConnector";
import RequestSender from "../utils/RequestSender";

const ROUTE_TOPOLOGY_TERMINATE = "/topology/terminate/:topologyId";

export default class Terminator {

    private requestedTerminations: Container;
    private httpServer: any;

    /**
     * @param {number} port
     * @param {ICounterStorage} storage
     * @param {MultiProbeConnector|null} multiProbe
     */
    constructor(
        private port: number,
        private storage: ICounterStorage,
        private multiProbe?: MultiProbeConnector,
    ) {
        this.requestedTerminations = new Container();
        this.prepareHttpServer();
    }

    /**
     *
     */
    public async startServer(): Promise<void> {
        return this.httpServer.listen(this.port, () => {
            logger.info(`Topology terminator is listening on port '${this.port}'`);
        });
    }

    /**
     *
     */
    public stopServer(): Promise<void> {
        return this.httpServer.close();
    }

    /**
     * Checks if topology can be terminated and if so, send http request about it
     */
    public async tryTerminate(topologyId: string): Promise<boolean> {
        if (!this.requestedTerminations.has(topologyId)) {
            return false;
        }

        const isRunning = await this.storage.hasSome(topologyId);
        if (isRunning) {
            return false;
        }

        logger.debug("Topology can be terminated now.", {topology_id: topologyId});

        if (this.multiProbe) {
            this.multiProbe.removeTopology(topologyId);
        }

        this.sendTerminateRequest(topologyId);

        return true;
    }

    /**
     * Creates http server to handle termination requests
     */
    private prepareHttpServer() {
        const server = express();

        server.get(ROUTE_TOPOLOGY_TERMINATE, (req, resp) => {
            try {
                logger.info(`Terminator received termination request. ${JSON.stringify(req.params)}`);
                this.handleTerminateRequest(req, resp);

                resp.status(200).send("Topology will be terminated as soon as possible.");
            } catch (e) {
                resp.status(400).send(e.message);
            }
        });

        this.httpServer = server;
    }

    private handleTerminateRequest(req: Request, resp: Response): void {
        if (!req.params || !req.params.topologyId) {
            throw new Error("Missing topologyId");
        }

        const topologyId = req.params.topologyId;
        const reqHeaders: any = req.headers;
        const headers = new Headers(reqHeaders);
        if (!headers.hasPFHeader(Headers.TOPOLOGY_DELETE_URL)) {
            throw new Error(`Missing PF header "pf-${Headers.TOPOLOGY_DELETE_URL}"`);
        }

        this.requestedTerminations.set(topologyId, headers.getPFHeader(Headers.TOPOLOGY_DELETE_URL));

        this.tryTerminate(topologyId);
    }

    /**
     * Sends http request to url previously given
     *
     * @param {string} topologyId
     */
    private sendTerminateRequest(topologyId: string) {
        const terminateOptions = {
            url: this.requestedTerminations.get(topologyId),
            method: "GET",
            timeout: 5000,
        };

        RequestSender.send(terminateOptions);
    }

}
