import * as express from "express";
import * as request from "request";
import logger from "../logger/Logger";
import {INodeConfig} from "./Configurator";

const DEFAULT_HTTP_PORT = 8007;
const HTTP_PROBE_PATH = "/status";
const HTTP_TIMEOUT = 10000;

interface INodeInfo {
    node: string;
    url: string;
    code: number;
    body: string;
    err: string;
}

interface IProbeNodeResult {
    node: string;
    status: boolean;
    url: string;
    code: number;
    message: string;
}

export interface IProbeResult {
    id: string;
    status: boolean;
    message: string;
    nodes: IProbeNodeResult[];
}

class Probe {

    private port: number;
    private topologyId: string;
    private nodes: INodeConfig[];

    /**
     *
     * @param {string} topologyId
     * @param {number} port
     */
    constructor(topologyId: string, port?: number) {
        this.topologyId = topologyId;
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

            const nodesInfo: INodeInfo[] = [];

            this.nodes.forEach((node: INodeConfig) => {
                request(node.debug.url, (err, response, body) => {
                    total += 1;

                    if (!err && response.statusCode && response.statusCode === 200) {
                        ready += 1;
                    } else {
                        failed += 1;
                    }

                    nodesInfo.push({
                        node: node.id,
                        url: node.debug.url,
                        code: response ? response.statusCode : 500,
                        body,
                        err,
                    });

                    if (!resolved && this.nodes.length === total) {
                        resolved = true;
                        resolve(this.composeProbeResult(total, ready, nodesInfo));
                    }
                });
            });

            setTimeout(() => {
                if (!resolved) {
                    resolved = true;
                    reject(this.composeProbeResult(total, ready, nodesInfo));
                }
            }, HTTP_TIMEOUT);
        });
    }

    /**
     *
     * @param {number} total
     * @param {number} success
     * @param {INodeInfo[]} nodesInfo
     * @return {IProbeResult}
     */
    private composeProbeResult(total: number, success: number, nodesInfo: INodeInfo[]): IProbeResult {
        const nodes: IProbeNodeResult[] = [];
        nodesInfo.forEach((n: INodeInfo) => {
            nodes.push({
                node: n.node,
                status: n.code === 200,
                url: n.url,
                code: n.code,
                message: n.err ? n.err : n.body,
            });
        });

        return {
            id: this.topologyId,
            status: success === total,
            message: `${success}/${total} nodes ready.`,
            nodes,
        };
    }

}

export default Probe;
