#!/usr/bin/env node

import * as fs from "fs";
import * as yargs from "yargs";
import IStoppable from "../IStoppable";
import logger from "../logger/Logger";
import Pipes from "../Pipes";
import {ITopologyConfig} from "../topology/Configurator";

const SIGTERM_TIMEOUT = 5000;

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

const loadTopologyConfigFromFile = (): ITopologyConfig => {
    try {
        return JSON.parse(fs.readFileSync("topology/topology.json", "utf8"));
    } catch (e) {
        logger.error("Cannot start program: ", {error: e, node_id: argv.service});
        // tslint:disable-next-line
        console.error(e.message);
        process.exit(126);
    }
};

const main = async () => {
    process.env.PIPES_NODE_TYPE = `pipes_${argv.service}`;

    const emtpyTopologyConfig: any = {};
    let pipes: Pipes;
    let svc: IStoppable;

    switch (argv.service) {
        case "multi_counter":
            // TODO - refactor Pipes not to reqiure topology.json in this case of starting multi_counter
            // Fake topology config (irrelevant for multi-counter)
            pipes = new Pipes(emtpyTopologyConfig);
            svc = await pipes.startMultiCounter();
            break;
        case "repeater":
            // TODO - refactor Pipes not to reqiure topology.json in this case of starting multi_counter
            // Fake topology config (irrelevant for multi-counter)
            pipes = new Pipes(emtpyTopologyConfig);
            svc = await pipes.startRepeater();
            break;
        case "multi_bridge":
            pipes = new Pipes(loadTopologyConfigFromFile());
            await pipes.startMultiBridge();
            break;
        case "bridge":
            // DEPRECATED
            logger.error(`Deprecated service: "${argv.service}". Use multi_bridge instead.`);
            process.exit(126);
            // pipes = new Pipes(loadTopologyConfigFromFile());
            // await pipes.startBridge(argv.id);
            break;
        default:
            logger.error(`Unknown service: "${argv.service}"`);
            process.exit(126);
    }

    process.on("SIGTERM", async () => {
        logger.info("SIGTERM received");
        process.exit(0);
    });

    process.on("SIGINT", async () => {
        logger.info("SIGINT received");

        // Force hard exit after timeout
        setTimeout(() => { process.exit(0); }, SIGTERM_TIMEOUT);

        if (!svc) {
            logger.info("Graceful shutdown - nothing to shutdown");
            return;
        }

        await svc.stop();
        logger.info("Graceful shutdown successful.");
        process.exit(0);
    });
};

main();
