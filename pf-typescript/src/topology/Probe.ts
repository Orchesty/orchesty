import * as express from "express";
import logger from "lib-nodejs/dist/src/logger/Logger";
import * as request from "request";
import { INodeConfig } from "./Configurator";

const DEFAULT_HTTP_PORT = 8007;
const HTTP_PROBE_PATH = "/status";
const HTTP_TIMEOUT = 10000;

export enum Status {
    SUCCESS = 200,
    ERROR = 503,
}

class Probe {

    private port: number;
    private nodes: INodeConfig[];

    /**
     *
     * @param {number} port
     */
    constructor(port?: number) {
        this.port = port || DEFAULT_HTTP_PORT;
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
                .then((res: {status: Status, message: string}) => {
                    resp
                        .set("Accept", "application/json")
                        .status(res.status)
                        .send(res.message);
                })
                .catch((err: Error) => {
                    resp
                        .set("Accept", "application/json")
                        .status(Status.ERROR)
                        .send(`Error: ${err}`);
                });
        });

        return new Promise((resolve) => {
            app.listen(this.port, () => {
                logger.info(`Topology Probe listening info on port: ${this.port}`);
                resolve();
            });
        });
    }

    /**
     * @return {Promise}
     * @private
     */
    public checkTopology(): Promise<{status: Status, message: string}> {
        return new Promise((resolve) => {
            let resolved = false;
            let ready = 0;
            let failed = 0;
            let total = 0;
            const failedInfo: Array<{ node: string, url: string, code: number, body: string, err: string }> = [];

            this.nodes.forEach((node: INodeConfig) => {
                request(node.debug.url, (err, response, body) => {
                    total += 1;

                    if (!err && response.statusCode && response.statusCode === Status.SUCCESS) {
                        ready += 1;
                    } else {
                        failed += 1;
                        failedInfo.push({ node: node.id, url: node.debug.url, code: response.statusCode, body, err });
                    }

                    if (!resolved && this.nodes.length === total) {
                        resolved = true;
                        if (total === ready) {
                            resolve({ status: Status.SUCCESS, message: `All ${ready} nodes are ready.` });
                        } else {
                            let msg = `Topology status: ${ready}/${this.nodes.length} nodes ready.`;
                            msg = `${msg} Failed: ${JSON.stringify(failedInfo)}`;
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

export default Probe;
