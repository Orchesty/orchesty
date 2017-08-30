#!/usr/bin/env node

// TODO get topology from config microservice
import logger from "lib-nodejs/dist/src/logger/Logger";
import Pipes from "../Pipes";
import { exampleTopo } from "../topology";

const pipes = new Pipes(exampleTopo);

const file = "./docker-compose.yml";

pipes.generateDockerCompose(file);

setTimeout(() => {
    process.exit(0);
}, 1000);
