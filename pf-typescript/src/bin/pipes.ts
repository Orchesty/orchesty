#!/usr/bin/env node

// TODO get topology from config microservice
import logger from "lib-nodejs/dist/src/logger/Logger";
import * as yargs from "yargs";
import Pipes from "../Pipes";
import { exampleTopo } from "../topology";

const pipes = new Pipes(exampleTopo);

const argv = yargs
    .usage("Usage: $0 start <services|node> [options]")
    .command("start <services|node>", "Starts concrete node or topology complementary services")
    .option("id", {
        describe: "Node ID to start",
        type: "string",
    })
    .demandCommand(1, "You need to specify command.")
    .help()
    .argv;

switch (argv.services) {
    case "services":
        Promise.all([
            pipes.startCounter(),
            pipes.startProbe(),
        ]).then(() => {
            logger.info("All services are running.");
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
