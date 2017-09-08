#!/usr/bin/env node

import * as fs from "fs";
import * as config from "../../src/config";
import Pipes from "../../src/Pipes";
import DockerComposeGenerator from "./DockerComposeGenerator";

const topologyConfig = JSON.parse(fs.readFileSync("topology.json", "utf8"));
const pipes = new Pipes(topologyConfig);

DockerComposeGenerator.generate(
    pipes.getTopologyConfig().nodes,
    config.amqpConnectionOptions,
    "./docker-compose.yml",
);

setTimeout(() => {
    process.exit(0);
}, 1000);
