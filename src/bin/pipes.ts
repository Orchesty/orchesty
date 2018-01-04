#!/usr/bin/env node

import * as fs from "fs";
import * as yargs from "yargs";
import logger from "../logger/Logger";
import Pipes from "../Pipes";
import {ITopologyConfig} from "../topology/Configurator";

process.on("unhandledRejection", (err) => {
    logger.error("Unhandled rejection", err);
    process.exit(1);
});

const argv = yargs
    .usage("Usage: $0 start <service> [options]")
    .command("start <service>", "Starts concrete node or topology complementary services")
    .option("id", {
        describe: "Node ID to start",
        type: "string",
    })
    .demandCommand(1, "You need to specify command.")
    .help()
    .argv;

let topologyConfig: ITopologyConfig;
try {
    topologyConfig = JSON.parse(fs.readFileSync("topology/topology.json", "utf8"));
} catch (e) {
    logger.error("Cannot start program: ", {error: e, node_id: argv.service});
    // tslint:disable-next-line
    console.error(e.message);
    process.exit(126);
}

const pipes = new Pipes(topologyConfig);

process.env.PIPES_NODE_TYPE = `pipes_${argv.service}_${topologyConfig.id}`;

switch (argv.service) {
    case "counter":
        pipes.startCounter();
        break;
    case "multi_counter":
        pipes.startMultiCounter();
        break;
    case "probe":
        pipes.startProbe();
        break;
    case "repeater":
        pipes.startRepeater();
        break;
    case "multi_bridge":
        process.env.PIPES_NODE_TYPE = `pipes_node`;
        pipes.startMultiBridge();
        break;
    case "bridge":
        process.env.PIPES_NODE_TYPE = `pipes_node`;
        pipes.startNode(argv.id);
        break;
    default:
}
