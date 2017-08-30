import * as express from "express";
import logger from "lib-nodejs/dist/src/logger/Logger";
import * as request from "request";
import { INodeConfig } from "./Configurator";

const HTTP_PORT = 8007;
const HTTP_PROBE_PATH = "/topology-status";
const HTTP_TIMEOUT = 10000;

export enum Status {
    SUCCESS = 200,
    ERROR = 503,
}

class TopologyReadinessProbe {

    private port: number;
    private nodes: INodeConfig[];

    /**
     *
     * @param {number} port
     */
    constructor(port?: number) {
        this.port = port || parseInt(process.env.HTTP_PORT, 10) || HTTP_PORT;
        this.nodes = [];
    }

    /**
     *
     * @param {INodeConfig} node
     */
    public addNode(node: INodeConfig) {
        this.nodes.push(node);
    }

    /**
     * Starts readiness probe server
     */
    public start(): Promise<void> {
        const app = express();

        app.get(HTTP_PROBE_PATH, (req, resp) => {
            this.checkTopology()
                .then((res: {status: number, message: string}) => {
                    resp.status(res.status).send(res.message);
                })
                .catch((err: Error) => {
                    resp.status(Status.ERROR).send(`Error: ${err}`);
                });
        });

        return new Promise((resolve) => {
            const server = app.listen(this.port, () => {
                logger.debug(`Topology Probe listening info on: ${server.address().address}:${server.address().port}`);
                resolve();
            });
        });
    }

    /**
     * @return {Promise}
     * @private
     */
    private checkTopology() {
        return new Promise((resolve) => {
            let resolved = false;
            let ready = 0;
            let failed = 0;
            let total = 0;
            const failedUrls: Array<{ url: string, err: string }> = [];

            this.nodes.forEach((node: INodeConfig) => {
                request(node.debug.url, (err, response) => {
                    total += 1;

                    if (!err && response.statusCode && response.statusCode === Status.SUCCESS) {
                        ready += 1;
                    } else {
                        failed += 1;
                        failedUrls.push({ url: node.debug.url, err });
                    }

                    if (!resolved && this.nodes.length === total) {
                        resolved = true;
                        if (total === ready) {
                            resolve({ status: Status.SUCCESS, message: `All ${ready} nodes are ready.` });
                        } else {
                            let msg = `Topology status: ${ready} of ${this.nodes.length} nodes ready.`;
                            msg = `${msg} Failed: ${JSON.stringify(failedUrls)}`;
                            resolve({ status: Status.ERROR, message: msg });
                        }
                    }
                });
            });

            setTimeout(() => {
                if (!resolved) {
                    resolved = true;
                    resolve({ status: Status.ERROR, message: "Timeout reached." });
                }
            }, HTTP_TIMEOUT);
        });
    }

}

export default TopologyReadinessProbe;
