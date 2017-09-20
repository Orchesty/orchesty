import { assert } from "chai";
import "mocha";

import * as express from "express";
import * as rp from "request-promise";
import {default as Configurator, INodeConfig} from "../../src/topology/Configurator";
import Probe, {IProbeResult} from "../../src/topology/Probe";

const topo = Configurator.createConfigFromSkeleton(
    {
        name: "probe-test",
        nodes: [
            {
                id: "node1",
                next: ["node2"],
                debug: {
                    port: 6001,
                    host: "localhost",
                    url: "http://localhost:6001/status",
                },
            },
            {
                id: "node2",
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
        const probe = new Probe(8005);
        topo.nodes.forEach((node: INodeConfig) => {
             probe.addNode(node);
        });
        return probe.start()
            .then(() => {
                return rp("http://localhost:8005/status");
            })
            .then((resp: string) => {
                const result: IProbeResult = JSON.parse(resp);
                assert.isFalse(result.status);
                assert.equal(result.message, "0/2 nodes ready.");
            });
    });

    it("should state that all nodes are running", () => {
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

        const probe = new Probe(8006);
        topo.nodes.forEach((node: INodeConfig) => {
            probe.addNode(node);
        });
        return probe.start()
            .then(() => {
                return rp("http://localhost:8006/status");
            })
            .then((resp: string) => {
                const result: IProbeResult = JSON.parse(resp);
                assert.equal(result.message, "2/2 nodes ready.");
                assert.equal(result.failed.length, 0);
                m1server.close();
                m2server.close();
            });
    });

    it("should state that first node is prepared, but the second is not", () => {
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

        const probe = new Probe(8008);
        topo.nodes.forEach((node: INodeConfig) => {
            probe.addNode(node);
        });
        return probe.start()
            .then(() => {
                return rp("http://localhost:8008/status");
            })
            .then((resp: string) => {
                const result: IProbeResult = JSON.parse(resp);
                assert.equal(result.message, "1/2 nodes ready.");
                assert.equal(result.failed.length, 1);
                assert.deepEqual(result.failed[0], {
                    node: topo.nodes[1].id,
                    url: topo.nodes[1].debug.url,
                    code: 500,
                    message: "Worker down",
                });
                m1server.close();
                m2server.close();
            });
    });
});
