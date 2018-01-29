import {Channel} from "amqplib";
import {Publisher} from "amqplib-plus/dist/lib/Publisher";
import * as fs from "fs";
import logger from "../logger/Logger";
import {IAmqpFaucetSettings} from "../node/faucet/AmqpFaucet";
import Pipes from "../Pipes";
import {ITopologyConfig} from "../topology/Configurator";

const POPULATOR_COUNT = parseInt(process.env.POPULATOR_COUNT, 10) || 100;
const POPULATOR_QUEUE = process.env.POPULATOR_QUEUE || "";

const loadTopologyConfigFromFile = (): ITopologyConfig => {
    try {
        return JSON.parse(fs.readFileSync("topology/topology.json", "utf8"));
    } catch (e) {
        logger.error("Cannot start program: ", {error: e});
        process.exit(126);
    }
};

const pipes = new Pipes(loadTopologyConfigFromFile());
const firstFaucet: IAmqpFaucetSettings = pipes.getTopologyConfig(true).nodes[0].faucet.settings;
const inQueue = firstFaucet.queue;

if (POPULATOR_QUEUE !== "") {
    inQueue.name = POPULATOR_QUEUE;
}

logger.info(`Populator will publish ${POPULATOR_COUNT} messages to ${firstFaucet.queue.name}`);

const conn = pipes.getDIContainer().get("amqp.connection");
const publisher = new Publisher(conn, async (ch: Channel) => {
    await ch.assertQueue(inQueue.name, inQueue.options);
});

const proms = [];

for (let i = 1; i <= POPULATOR_COUNT; i++) {
     const prom = publisher.sendToQueue(
        inQueue.name,
        new Buffer("populator test"),
        {
            headers: {
                "pf-correlation-id" : `corr-${i}`,
                "pf-process-id": `process-${i}`,
                "pf-parent-id": "",
                "pf-sequence-id": "1",
            },
        },
    ).then(() => {
        logger.info(`#${i} populator message sent to ${inQueue.name}.`);
    });

     proms.push(prom);
}

Promise.all(proms).then(() => {
    // In 10s hopefully all messages should be published to broker
    setTimeout(() => { process.exit(0); }, 10000);
});
