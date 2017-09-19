import * as express from "express";
import * as request from "request";
import logger from "../logger/Logger";
import { INodeConfig } from "./Configurator";

const DEFAULT_HTTP_PORT = 8007;
const HTTP_PROBE_PATH = "/status";
const HTTP_TIMEOUT = 10000;

interface IFailureInfo {
    node: string;
    url: string;
    code: number;
    body: string;
    err: string;
}

interface IProbeNodeFailure {
    node: string;
    url: string;
    code: number;
    message: string;
}

export interface IProbeResult {
    status: boolean;
    message: string;
    failed: IProbeNodeFailure[];
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
                .then((result: IProbeResult) => {
                    resp
                        .set("Accept", "application/json")
                        .status(200)
                        .send(JSON.stringify(result));
                })
                .catch((result: IProbeResult) => {
                    result.status = false;
                    result.message = "Timeout reached.";
                    resp
                        .set("Accept", "application/json")
                        .status(503)
                        .send(JSON.stringify(result));
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
    public checkTopology(): Promise<IProbeResult> {
        return new Promise((resolve, reject) => {
            let resolved = false;
            let ready = 0;
            let failed = 0;
            let total = 0;

            const failedInfo: IFailureInfo[] = [];

            this.nodes.forEach((node: INodeConfig) => {
                request(node.debug.url, (err, response, body) => {
                    total += 1;

                    if (!err && response.statusCode && response.statusCode === 200) {
                        ready += 1;
                    } else {
                        failed += 1;
                        failedInfo.push({
                            node: node.id,
                            url: node.debug.url,
                            code: response ? response.statusCode : 500,
                            body,
                            err,
                        });
                    }

                    if (!resolved && this.nodes.length === total) {
                        resolved = true;

                        resolve(this.composeProbeResult(total, failedInfo));
                    }
                });
            });

            setTimeout(() => {
                if (!resolved) {
                    resolved = true;
                    reject(this.composeProbeResult(total, failedInfo));
                }
            }, HTTP_TIMEOUT);
        });
    }

    private composeProbeResult(total: number, failures: IFailureInfo[]): IProbeResult {
        const failed: IProbeNodeFailure[] = [];
        failures.forEach((f: IFailureInfo) => {
            failed.push({
                node: f.node,
                url: f.url,
                code: f.code,
                message: f.err ? f.err : f.body,
            });
        });

        return {
            status: failures.length === 0,
            message: `${total - failures.length}/${total} nodes ready.`,
            failed,
        };
    }

}

export default Probe;
