import * as express from "express";
import logger from "../../logger/Logger";
import Headers from "../../message/Headers";
import ICounterStorage from "../counter/storage/ICounterStorage";
import RequestSender from "../util/RequestSender";

const ROUTE_TOPOLOGY_TERMINATE = "/topology/terminate/:topologyId";

export default class Terminator {

    private terminationRequested: boolean;
    private terminationUrl: string;
    private httpServer: any;

    constructor(private storage: ICounterStorage) {
        this.terminationRequested = false;
        this.prepareHttpServer();
        this.httpServer.listen(httpPort, () => {
            logger.info(`Topology terminator listening for termination requests on port '${httpPort}'`);
        });
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

        this.probe.removeTopology(topologyId);

        // Should terminate -> send request and expect being terminated
        const terminateOptions = {method: "GET", url: this.terminationUrl, timeout: 5000};
        RequestSender.send(terminateOptions);
    }

    /**
     * Creates http server to handle termination requests
     */
    private prepareHttpServer() {
        const app = express();

        app.get(ROUTE_TOPOLOGY_TERMINATE, (req, resp) => {
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

        this.httpServer = app;
    }

}
