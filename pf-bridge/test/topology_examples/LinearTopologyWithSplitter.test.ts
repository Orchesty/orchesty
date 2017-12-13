import { assert } from "chai";
import "mocha";

import {Channel, Message} from "amqplib";
import {Connection} from "amqplib-plus/dist/lib/Connection";
import {Publisher} from "amqplib-plus/dist/lib/Publisher";
import {SimpleConsumer} from "amqplib-plus/dist/lib/SimpleConsumer";
import * as config from "../../src/config";
import {ResultCode} from "../../src/message/ResultCode";
import Pipes from "../../src/Pipes";
import {ITopologyConfigSkeleton} from "../../src/topology/Configurator";
import {ICounterProcessInfo} from "../../src/topology/counter/CounterProcess";

const testTopology: ITopologyConfigSkeleton = {
    id: "linear-topo-with-splitter",
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
    it("complete flow of messages till the end", (done) => {
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

        Promise.all([
            pip.startCounter(8557),
            pip.startNode(testTopology.nodes[0].id),
            pip.startNode(testTopology.nodes[1].id),
            pip.startNode(testTopology.nodes[2].id),
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
                    return new Promise((resolve) => {
                        ch.assertQueue(counterResultQueue.name, counterResultQueue.options)
                            .then(() => {
                                return ch.purgeQueue(counterResultQueue.name);
                            })
                            .then(() => {
                                return ch.bindQueue(
                                    counterResultQueue.name,
                                    pip.getTopologyConfig().counter.pub.exchange.name,
                                    pip.getTopologyConfig().counter.pub.routing_key,
                                );
                            })
                            .then(() => {
                                resolve();
                            });
                    });
                },
                (msg: Message) => {
                    // In this fn we evaluate expected incoming message and state if test is OK or failed
                    const data: ICounterProcessInfo = JSON.parse(msg.content.toString());
                    assert.equal(data.process_id, msgHeaders.headers["pf-process-id"]);
                    assert.equal(data.total, 6);
                    assert.equal(data.ok, 6);
                    assert.equal(data.nok, 0);
                    const trace: string[] = [];
                    data.messages.forEach((info) => {
                        assert.equal(info.resultCode, ResultCode.SUCCESS);
                        trace.push(info.node);
                    });
                    assert.deepEqual(
                        trace,
                        [
                            testTopology.nodes[0].id,
                            testTopology.nodes[1].id,
                            // in node-b message should have been split to 4 sub-messages
                            testTopology.nodes[2].id,
                            testTopology.nodes[2].id,
                            testTopology.nodes[2].id,
                            testTopology.nodes[2].id,
                        ],
                    );
                    done();
                },
            );

            return resultConsumer.consume(counterResultQueue.name, {});
        })
        .then(() => {
            // Publish messages to the first queue
            const publisher = new Publisher(
                amqpConn,
                (ch: Channel) => {
                    return new Promise((resolve) => {
                        ch.assertQueue(firstQueue, {})
                            .then(() => {
                                return ch.purgeQueue(firstQueue);
                            })
                            .then(() => {
                                resolve();
                            });
                    });
                },
            );
            return publisher.sendToQueue(firstQueue, new Buffer(JSON.stringify(msgTestContent)), msgHeaders);
        });
    });

});
