#!/usr/bin/env node

import * as fs from "fs";
import Pipes from "../Pipes";

const topologyConfig = JSON.parse(fs.readFileSync("topology.json", "utf8"));
const pipes = new Pipes(topologyConfig);

const file = "./docker-compose.yml";

pipes.generateDockerCompose(file);

setTimeout(() => {
    process.exit(0);
}, 1000);
