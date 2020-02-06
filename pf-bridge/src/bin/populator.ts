import {Channel} from "amqplib";
import {Publisher} from "amqplib-plus";
import * as fs from "fs";
import logger from "../logger/Logger";
import {IAmqpFaucetSettings} from "../node/faucet/AmqpFaucet";
import Pipes from "../Pipes";
import {ITopologyConfig} from "../topology/Configurator";

const POPULATOR_COUNT = parseInt(process.env.POPULATOR_COUNT, 10) || 1000;
const POPULATOR_BATCH = parseInt(process.env.POPULATOR_BATCH, 10) || 500;
const POPULATOR_BATCH_TIMEOUT = parseInt(process.env.POPULATOR_BATCH_TIMEOUT, 10) || 1000;
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

for (let i = 1; i <= POPULATOR_COUNT; i++) {
    setTimeout(() => {
        sendMessage(i);
    }, Math.floor(i / POPULATOR_BATCH) * POPULATOR_BATCH_TIMEOUT);
}

// const content = JSON.stringify({
//     event: "data",
//     data: JSON.stringify({
//         bids: [],
//         asks: [],
//     }),
// });

const content = JSON.stringify({
    bids: "Lorem ipsum dolor sit amet, consectetur adipiscing elit.",
    asks: "Lorem ipsum dolor sit amet, consectetur adipiscing elit.",
    foo: "Lorem ipsum dolor sit amet, consectetur adipiscing elit.",
    bar: "Lorem ipsum dolor sit amet, consectetur adipiscing elit.",
});

const sendMessage = (i: number) => {
    publisher.sendToQueue(
        inQueue.name,
        Buffer.from(content),
        {
            headers: {
                "pf-correlation-id" : `corr-${i}`,
                "pf-process-id": `process-${i}`,
                "pf-parent-id": "",
                "pf-sequence-id": "1",
                "pf-topology-id": "topo-id",
            },
        },
    ).then(() => {
        logger.info(`#${i} populator message sent to ${inQueue.name}.`);
    });
};

// In 5s after the last batch everything should be published
setTimeout(() => { process.exit(0); }, (Math.floor(POPULATOR_COUNT / POPULATOR_BATCH) + 5) * POPULATOR_BATCH_TIMEOUT);
