import * as express from "express";
import {Container} from "hb-utils/dist/lib/Container";
import logger from "../../logger/Logger";
import Headers from "../../message/Headers";
import ICounterStorage from "../counter/storage/ICounterStorage";
import MultiProbeConnector from "../probe/MultiProbeConnector";
import RequestSender from "../util/RequestSender";

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
     * Checks if counter can be terminated and if so, send http request about it
     */
    public async tryTerminate(topologyId: string): Promise<boolean> {
        if (!this.requestedTerminations.has(topologyId)) {
            return Promise.resolve(false);
        }

        const isSomeRunning = await this.storage.hasSome(topologyId);
        if (isSomeRunning) {
            return Promise.resolve(false);
        }

        if (this.multiProbe) {
            this.multiProbe.removeTopology(topologyId);
        }

        // Should terminate -> send request and expect being terminated
        const terminateOptions = {
            url: this.requestedTerminations.get(topologyId),
            method: "GET",
            timeout: 5000,
        };

        RequestSender.send(terminateOptions);

        return Promise.resolve(true);
    }

    /**
     * Creates http server to handle termination requests
     */
    private prepareHttpServer() {
        const server = express();

        server.get(ROUTE_TOPOLOGY_TERMINATE, (req, resp) => {
            if (!req.params || !req.params.topologyId) {
                return resp.status(400).send("Missing topologyId");
            }

            const topologyId = req.params.topologyId;
            const reqHeaders: any = req.headers;
            const headers = new Headers(reqHeaders);
            if (!headers.hasPFHeader(Headers.TOPOLOGY_DELETE_URL)) {
                return resp.status(400).send(`Missing PF header "pf-${Headers.TOPOLOGY_DELETE_URL}"`);
            }

            resp.status(200).send("Topology will be terminated as soon as possible.");

            logger.info(`Terminator received termination request. ${JSON.stringify(req.params)}`);

            this.requestedTerminations.set(topologyId, headers.getPFHeader(Headers.TOPOLOGY_DELETE_URL));

            this.tryTerminate(topologyId);
        });

        this.httpServer = server;
    }

}
