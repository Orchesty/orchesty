import * as express from "express";
import * as request from "request";
import logger from "../../logger/Logger";
import {INodeConfig, INodeLabel} from "../Configurator";

interface INodeInfo {
    label: INodeLabel;
    url: string;
    code: number;
    body: string;
    err: string;
}

interface IProbeNodeResult {
    id: string;
    node_id: string;
    node_name: string;
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

export interface IProbeSettings {
    port: number;
    path: string;
    timeout: number;
}

class Probe {

    private nodes: INodeConfig[];

    /**
     *
     * @param {string} topologyId
     * @param {IProbeSettings} settings
     */
    constructor(
        private topologyId: string,
        private settings: IProbeSettings,
    ) {
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

        app.get(this.settings.path, (req, resp) => {
            this.checkTopology()
                .then((result: IProbeResult) => {
                    resp.set("Content-Type", "application/json");
                    resp.status(200);
                    resp.send(JSON.stringify(result));
                });
        });

        return new Promise((resolve) => {
            app.listen(this.settings.port, () => {
                logger.info(`Topology Probe listening info on port: ${this.settings.port}`);
                resolve();
            });
        });
    }

    /**
     * @return {Promise}
     * @private
     */
    public checkTopology(): Promise<IProbeResult> {
        return new Promise((resolve) => {
            let resolved = false;
            let ready = 0;
            let failed = 0;
            let total = 0;

            const nodesInfo: INodeInfo[] = [];

            this.nodes.forEach((node: INodeConfig) => {
                const requestOptions = {
                    url: node.debug.url,
                    method: "GET",
                    timeout: this.settings.timeout,
                };

                request(requestOptions, (err, response, body) => {
                    total += 1;

                    if (!err && response.statusCode && response.statusCode === 200) {
                        ready += 1;
                    } else {
                        failed += 1;
                    }

                    nodesInfo.push({
                        label: node.label,
                        url: node.debug.url,
                        code: response ? response.statusCode : 500,
                        body,
                        err: err ? err.code : "",
                    });

                    if (!resolved && this.nodes.length === total) {
                        resolved = true;
                        resolve(this.composeProbeResult(total, ready, nodesInfo));
                    }
                });
            });
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
                id: n.label.id,
                node_id: n.label.node_id,
                node_name: n.label.node_name,
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
