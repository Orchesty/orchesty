import {Channel} from "amqplib";
import {Publisher} from "amqplib-plus/dist/lib/Publisher";
import * as fs from "fs";
import logger from "../logger/Logger";
import {IAmqpFaucetSettings} from "../node/faucet/AmqpFaucet";
import Pipes from "../Pipes";
import {ITopologyConfig} from "../topology/Configurator";

const POPULATOR_COUNT = parseInt(process.env.POPULATOR_COUNT, 10) || 100;

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

logger.info(`Populator will publish ${POPULATOR_COUNT} messages to ${firstFaucet.queue.name}`);

const conn = pipes.getDIContainer().get("amqp.connection");
const publisher = new Publisher(conn, async (ch: Channel) => {
    await ch.assertQueue(firstFaucet.queue.name, firstFaucet.queue.options);
});

for (let i = 0; i < POPULATOR_COUNT; i++) {
    publisher.sendToQueue(
        firstFaucet.queue.name,
        new Buffer("populator test"),
        {
            headers: {
                "pf-correlation-id" : i,
                "pf-process-id": i,
                "pf-parent-id": "",
                "pf-sequence-id": "1",
            },
        },
    ).then(() => {
        logger.info(`#${i + 1} populator message sent.`);
    });
}
