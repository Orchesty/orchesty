#!/usr/bin/env node

import * as fs from "fs";
import logger from "lib-nodejs/dist/src/logger/Logger";
import * as yargs from "yargs";
import * as config from "../config";
import Pipes from "../Pipes";

const topologyConfig = JSON.parse(fs.readFileSync("topology.json", "utf8"));
const pipes = new Pipes(topologyConfig, config.amqpConnectionOptions);

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

switch (argv.service) {
    case "counter":
        pipes.startCounter()
        .then(() => {
            logger.info("Counter is running.");
        });
        break;
    case "probe":
        pipes.startProbe()
            .then(() => {
                logger.info("Probe is running.");
            });
        break;
    case "node":
        logger.info(`Starting node '${argv.id}'`);
        pipes.startNode(argv.id)
            .then(() => {
                logger.info(`Node ${argv.id} is running.`);
            });
        break;
    default:
}
