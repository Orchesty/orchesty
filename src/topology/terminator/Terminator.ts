import * as express from "express";
import logger from "../../logger/Logger";
import Headers from "../../message/Headers";
import ICounterStorage from "../counter/storage/ICounterStorage";
import MultiProbeConnector from "../probe/MultiProbeConnector";
import RequestSender from "../util/RequestSender";

const ROUTE_TOPOLOGY_TERMINATE = "/topology/terminate/:topologyId";

// TODO - modify to multi-counter
export default class Terminator {

    private terminationRequested: boolean;
    private terminationUrl: string;
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
        this.terminationRequested = false;
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
    public async tryTerminate(topologyId: string) {
        if (!this.terminationRequested || !this.terminationUrl) {
            return;
        }

        const isSomeRunning = await this.storage.hasSome(topologyId);
        if (isSomeRunning) {
            return;
        }

        if (this.multiProbe) {
            this.multiProbe.removeTopology(topologyId);
        }

        // Should terminate -> send request and expect being terminated
        const terminateOptions = {method: "GET", url: this.terminationUrl, timeout: 5000};
        RequestSender.send(terminateOptions);
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

            // if (req.params.topologyId !== this.topologyId) {
            //     return resp.status(400).send(`Invalid topologyId "${req.params.topologyId}"`);
            // }

            const reqHeaders: any = req.headers;
            const headers = new Headers(reqHeaders);
            if (!headers.hasPFHeader(Headers.TOPOLOGY_DELETE_URL)) {
                return resp.status(400).send(`Missing PF header "pf-${Headers.TOPOLOGY_DELETE_URL}"`);
            }

            resp.status(200).send("Topology will be terminated as soon as possible.");

            logger.info(`Counter received termination request. ${JSON.stringify(req.params)}`);

            this.terminationRequested = true;
            this.terminationUrl = headers.getPFHeader(Headers.TOPOLOGY_DELETE_URL);
            this.tryTerminate(req.params.topologyId);
        });

        this.httpServer = server;
    }

}
