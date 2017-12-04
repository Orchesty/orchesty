#!/usr/bin/env node

import * as fs from "fs";
import * as yargs from "yargs";
import Pipes from "../Pipes";
import {ITopologyConfig} from "../topology/Configurator";

const topologyConfig: ITopologyConfig = JSON.parse(fs.readFileSync("topology/topology.json", "utf8"));

const pipes = new Pipes(topologyConfig);

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

process.env.PIPES_NODE_TYPE = `pipes_${argv.service}_${topologyConfig.id}`;

switch (argv.service) {
    case "counter":
        pipes.startCounter();
        break;
    case "probe":
        pipes.startProbe();
        break;
    case "repeater":
        pipes.startRepeater();
        break;
    case "multi_bridge":
        process.env.PIPES_NODE_TYPE = `pipes_node`;
        pipes.startAllNodes();
        break;
    case "bridge":
        process.env.PIPES_NODE_TYPE = `pipes_node`;
        pipes.startNode(argv.id);
        break;
    default:
}
