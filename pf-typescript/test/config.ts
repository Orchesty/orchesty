import {IOptions} from "lib-nodejs/dist/src/rabbitmq/Connection";
import { ITopologyConfigSkeleton } from "../src/topology/Configurator";

export const testAmqpConnectionOptions: IOptions = {
    host: process.env.RABBITMQ_HOST || "localhost",
    user: process.env.RABBITMQ_USER || "guest",
    pass: process.env.RABBITMQ_PASS || "guest",
    port: parseInt(process.env.RABBITMQ_PORT, 10) || 5672,
    vhost: process.env.RABBITMQ_VHOST || "/",
    heartbeat: parseInt(process.env.RABBITMQ_HEARTBEAT, 10) || 60,
};

export const exampleTopo: ITopologyConfigSkeleton = {
    name: "pf-example",
    nodes: [
        {
            id: "node_1",
            resequencer: true,
            faucet: {
                type: "faucet.http",
                settings: {
                    port: 3333,
                },
            },
            worker: { type: "worker.uppercase", settings: {} },
            next: ["node_2"],
            debug: {
                port: 8002,
                host: "localhost",
                url: "http://localhost:8002/status",
            },
        },
        {
            id: "node_2",
            resequencer: true,
            worker: { type: "worker.appender", settings: { suffix: "| something"} },
            next: [],
            debug: {
                port: 8003,
                host: "localhost",
                url: "http://localhost:8003/status",
            },
        },
    ],
};
