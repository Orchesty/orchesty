#!/usr/bin/env node

import * as fs from "fs";
import * as yargs from "yargs";
import Pipes from "../Pipes";

const topologyConfig = JSON.parse(fs.readFileSync("topology.json", "utf8"));
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

process.env.PIPES_NODE_TYPE = `pipes_${argv.service}_${argv.id}`;

switch (argv.service) {
    case "counter":
        pipes.startCounter();
        break;
    case "probe":
        pipes.startProbe();
        break;
    case "node":
        pipes.startNode(argv.id);
        break;
    default:
}
