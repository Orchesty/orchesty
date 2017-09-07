import { ITopologyConfigSkeleton } from "../src/topology/Configurator";

export const testTopology: ITopologyConfigSkeleton = {
    name: "test-topo",
    nodes: [
        {
            id: "first",
            resequencer: true,
            // faucet: {
            //     type: "faucet.http",
            //     settings: { port: "3500" },
            // },
            debug: {
                port: 4001,
                host: "localhost",
                url: "http://localhost:4001/status",
            },
            next: ["second"],
        },
        {
            id: "second",
            resequencer: true,
            worker: {
                type: "worker.http",
                settings: {
                    method: "post",
                    url: "http://localhost:3000/httpworker1/",
                    opts: {},
                },
            },
            debug: {
                port: 4002,
                host: "localhost",
                url: "http://localhost:4002/status",
            },
            next: ["third"],
        },
        {
            id: "third",
            resequencer: true,
            worker: {
                type: "worker.uppercase",
                settings: {},
            },
            debug: {
                port: 4003,
                host: "localhost",
                url: "http://localhost:4003/status",
            },
            next: [],
        },
    ],
};
