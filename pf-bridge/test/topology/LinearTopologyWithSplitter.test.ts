import { assert } from "chai";
import "mocha";

import {Channel, Message} from "amqplib";
import {Connection} from "amqplib-plus/dist/lib/Connection";
import {Publisher} from "amqplib-plus/dist/lib/Publisher";
import {SimpleConsumer} from "amqplib-plus/dist/lib/SimpleConsumer";
import * as config from "../../src/config";
import {redisStorageOptions} from "../../src/config";
import {persistentQueues} from "../../src/config";
import {ICounterProcessInfo} from "../../src/counter/CounterProcess";
import RedisStorage from "../../src/counter/storage/RedisStorage";
import {ResultCode} from "../../src/message/ResultCode";
import Pipes from "../../src/Pipes";
import Terminator from "../../src/terminator/Terminator";
import {ITopologyConfigSkeleton} from "../../src/topology/Configurator";

const testTopology: ITopologyConfigSkeleton = {
    id: "linear-topo-with-splitter",
    topology_id: "linear-topo-with-splitter",
    topology_name: "linear-topo-with-splitter",
    nodes: [
        {
            id: "node-a",
            debug: {
                port: 4111,
                host: "localhost",
                url: "http://localhost:4101/status",
            },
            next: ["node-b"],
        },
        {
            id: "node-b",
            worker: {
                type: "splitter.json",
                settings: {
                    node_id: "node-b",
                },
            },
            debug: {
                port: 4112,
                host: "localhost",
                url: "http://localhost:4102/status",
            },
            next: ["node-c"],
        },
        {
            id: "node-c",
            worker: {
                type: "worker.uppercase",
                settings: {},
            },
            debug: {
                port: 4103,
                host: "localhost",
                url: "http://localhost:4103/status",
            },
            next: [],
        },
    ],
};

const amqpConn = new Connection(config.amqpConnectionOptions);
const firstQueue = `pipes.${testTopology.id}.${testTopology.nodes[0].id}`;

describe("Linear topology with splitter test", () => {
    it("complete flow of messages till the end #integration", (testDone) => {
        const msgTestContent = [
            { val : "to be split 1"},
            { val : "to be split 2"},
            { val : "to be split 3"},
            { val : "to be split 4"},
        ];
        const msgHeaders = { headers: {
            "pf-correlation-id": "corrid",
            "pf-process-id": "test",
            "pf-parent-id": "",
            "pf-sequence-id": 0,
        }};

        const pip = new Pipes(testTopology);

        // manually set the terminator port not to collide with other tests
        const dic = pip.getDIContainer();
        dic.set("topology.terminator", () => new Terminator(8557, dic.get("counter.storage")));

        const redis = new RedisStorage(redisStorageOptions);
        redis.remove(testTopology.id, msgHeaders.headers["pf-process-id"]);

        Promise.all([
            pip.startCounter(),
            pip.startBridge(testTopology.nodes[0].id),
            pip.startBridge(testTopology.nodes[1].id),
            pip.startBridge(testTopology.nodes[2].id),
        ])
        .then(() => {
            // Prepares consumer of counter output
            // Prepares function for evaluation of test end
            const counterResultQueue = {
                name: "pipes.linear-topo-with-splitter.counter-result",
                options: {},
            };
            const resultConsumer = new SimpleConsumer(
                amqpConn,
                (ch: Channel) => {
                    return new Promise(async (resolve) => {
                        await ch.assertQueue(counterResultQueue.name, counterResultQueue.options);
                        await ch.purgeQueue(counterResultQueue.name);
                        await ch.bindQueue(
                            counterResultQueue.name,
                            pip.getTopologyConfig(false).counter.pub.exchange.name,
                            pip.getTopologyConfig(false).counter.pub.routing_key,
                        );
                        resolve();
                    });
                },
                (msg: Message) => {
                    // In this fn we evaluate expected incoming message and state if test is OK or failed
                    const data: ICounterProcessInfo = JSON.parse(msg.content.toString());
                    assert.equal(data.process_id, msgHeaders.headers["pf-process-id"]);
                    assert.equal(data.total, 5);
                    assert.equal(data.ok, 5);
                    assert.equal(data.nok, 0);
                    data.messages.forEach((info) => {
                        assert.equal(info.resultCode, ResultCode.SUCCESS);
                    });

                    testDone();
                },
            );

            return resultConsumer.consume(counterResultQueue.name, {});
        })
        .then(() => {
            // Publish messages to the first queue
            const publisher = new Publisher(
                amqpConn,
                (ch: Channel) => {
                    return new Promise(async (resolve) => {
                        await ch.assertQueue(firstQueue, { durable: persistentQueues });
                        await ch.purgeQueue(firstQueue);
                        resolve();
                    });
                },
            );
            return publisher.sendToQueue(firstQueue, Buffer.from(JSON.stringify(msgTestContent)), msgHeaders);
        });
    }).timeout(5000);

});
