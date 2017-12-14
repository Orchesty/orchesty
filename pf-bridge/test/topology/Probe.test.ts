import { assert } from "chai";
import "mocha";

import * as express from "express";
import * as rp from "request-promise";
import {default as Configurator, INodeConfig} from "../../src/topology/Configurator";
import Probe, {IProbeResult} from "../../src/topology/probe/Probe";

const topo = Configurator.createConfigFromSkeleton(
    false,
    {
        id: "probe-test",
        nodes: [
            {
                id: "node1",
                label: {
                    id: "node1",
                    node_id: "507f191e810c19729de860ea",
                    node_name: "a",
                    topology_id: "probe-test",
                },
                next: ["node2"],
                debug: {
                    port: 6001,
                    host: "localhost",
                    url: "http://localhost:6001/status",
                },
            },
            {
                id: "node2",
                label: {
                    id: "node2",
                    node_id: "607f191e810c19729de860cd",
                    node_name: "b",
                    topology_id: "probe-test",
                },
                next: [],
                debug: {
                    port: 6002,
                    host: "localhost",
                    url: "http://localhost:6002/status",
                },
            },
        ],
    },
);

describe("Probe", () => {
    it("should return that none of nodes is running", () => {
        const probe = new Probe("topoId", {port: 8003, path: "/status", timeout: 1000});
        topo.nodes.forEach((node: INodeConfig) => {
             probe.addNode(node);
        });
        return probe.start()
            .then(() => {
                return rp("http://localhost:8003/status");
            })
            .then((resp: string) => {
                const result: IProbeResult = JSON.parse(resp);
                assert.isFalse(result.status);
                assert.equal(result.message, "0/2 nodes ready.");
            });
    });

    it("should return that all nodes are running", () => {
        // Node1 server mock
        const mock1 = express();
        mock1.get("/status", (req, resp) => {
            resp.status(200).send();
        });
        const m1server = mock1.listen(topo.nodes[0].debug.port);

        // Node2 server mock
        const mock2 = express();
        mock2.get("/status", (req, resp) => {
            resp.status(200).send();
        });
        const m2server = mock2.listen(topo.nodes[1].debug.port);

        const probe = new Probe("topoId", {port: 8004, path: "/status", timeout: 1000});
        topo.nodes.forEach((node: INodeConfig) => {
            probe.addNode(node);
        });
        return probe.start()
            .then(() => {
                return rp("http://localhost:8004/status");
            })
            .then((resp: string) => {
                const result: IProbeResult = JSON.parse(resp);
                assert.equal(result.message, "2/2 nodes ready.");
                assert.equal(result.nodes.length, 2);
                m1server.close();
                m2server.close();
            });
    });

    it("should return that first node is prepared, but the second is not", () => {
        // Node1 server mock
        const mock1 = express();
        mock1.get("/status", (req, resp) => {
            resp.status(200).send("OK");
        });
        const m1server = mock1.listen(topo.nodes[0].debug.port);

        // Node2 server mock
        const mock2 = express();
        mock2.get("/status", (req, resp) => {
            resp.status(500).send("Worker down");
        });
        const m2server = mock2.listen(topo.nodes[1].debug.port);

        const probe = new Probe("topoId", {port: 8005, path: "/status", timeout: 1000});
        topo.nodes.forEach((node: INodeConfig) => {
            probe.addNode(node);
        });
        return probe.start()
            .then(() => {
                return rp("http://localhost:8005/status");
            })
            .then((resp: string) => {
                const result: IProbeResult = JSON.parse(resp);
                assert.equal(result.message, "1/2 nodes ready.");
                assert.equal(result.nodes.length, 2);
                assert.isFalse(result.status);
                assert.sameDeepMembers(
                    result.nodes,
                    [
                        {
                            id: topo.nodes[0].id,
                            node_id: topo.nodes[0].label.node_id,
                            node_name: topo.nodes[0].label.node_name,
                            url: topo.nodes[0].debug.url,
                            code: 200,
                            message: "OK",
                            status: true,
                        },
                        {
                            id: topo.nodes[1].id,
                            node_id: topo.nodes[1].label.node_id,
                            node_name: topo.nodes[1].label.node_name,
                            url: topo.nodes[1].debug.url,
                            code: 500,
                            message: "Worker down",
                            status: false,
                        },
                    ],
                );
                m1server.close();
                m2server.close();
            });
    });

    it("should return that all nodes are running second node check timeouted", () => {
        // Node1 server mock
        const mock1 = express();
        mock1.get("/status", (req, resp) => {
            resp.status(200).send();
        });
        const m1server = mock1.listen(topo.nodes[0].debug.port);

        // Node2 server mock
        const mock2 = express();
        mock2.get("/status", (req, resp) => {
            // return response after 500ms, which is greater then probe 200ms timeout
            setTimeout(() => {
                resp.status(200).send();
            }, 500);
        });
        const m2server = mock2.listen(topo.nodes[1].debug.port);

        const probe = new Probe("topoId", {port: 8006, path: "/status", timeout: 200});
        topo.nodes.forEach((node: INodeConfig) => {
            probe.addNode(node);
        });
        return probe.start()
            .then(() => {
                return rp("http://localhost:8006/status");
            })
            .then((resp: string) => {
                const result: IProbeResult = JSON.parse(resp);
                assert.equal(result.message, "1/2 nodes ready.");
                assert.equal(result.nodes.length, 2);
                m1server.close();
                m2server.close();
            });
    });
});
