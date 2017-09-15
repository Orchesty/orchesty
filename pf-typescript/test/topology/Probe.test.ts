import { assert } from "chai";
import "mocha";

import * as express from "express";
import * as rp from "request-promise";
import {default as Configurator, INodeConfig} from "../../src/topology/Configurator";
import Probe from "../../src/topology/Probe";

const topo = Configurator.createConfigFromSkeleton(
    {
        name: "probe-test",
        nodes: [
            {
                id: "node1",
                next: ["node2"],
                debug: {
                    port: 8001,
                    host: "localhost",
                    url: "http://localhost:8001/status",
                },
            },
            {
                id: "node1",
                next: [],
                debug: {
                    port: 8002,
                    host: "localhost",
                    url: "http://localhost:8002/status",
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
            .catch((err: any) => {
                assert.equal(503, err.statusCode);
                assert.include(err.message, "Topology status: 0 of 2 nodes ready.");
            });
    });

    it("should satet that all nodes are running", () => {
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
                assert.include(resp, "All 2 nodes are ready");
                m1server.close();
                m2server.close();
            });
    });

    it("should state that first node is prepared, but the second is not", () => {
        // Node1 server mock
        const mock1 = express();
        mock1.get("/status", (req, resp) => {
            resp.status(200).send();
        });
        const m1server = mock1.listen(topo.nodes[0].debug.port);

        // Node2 server mock
        const mock2 = express();
        mock2.get("/status", (req, resp) => {
            resp.status(500).send();
        });
        const m2server = mock2.listen(topo.nodes[1].debug.port);

        const probe = new Probe(8007);
        topo.nodes.forEach((node: INodeConfig) => {
            probe.addNode(node);
        });
        return probe.start()
            .then(() => {
                return rp("http://localhost:8006/status");
            })
            .catch((err: any) => {
                assert.equal(503, err.statusCode);
                assert.include(err.message, "Topology status: 1 of 2 nodes ready.");
                assert.include(err.message, "http://localhost:8002/status");
                m1server.close();
                m2server.close();
            });
    });
});
