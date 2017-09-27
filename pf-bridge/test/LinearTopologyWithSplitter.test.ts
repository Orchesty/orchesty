import { assert } from "chai";
import "mocha";

import {Channel, Message} from "amqplib";
import Connection from "lib-nodejs/dist/src/rabbitmq/Connection";
import Publisher from "lib-nodejs/dist/src/rabbitmq/Publisher";
import SimpleConsumer from "lib-nodejs/dist/src/rabbitmq/SimpleConsumer";
import * as config from "../src/config";
import {ResultCode} from "../src/message/ResultCode";
import {ITopologyConfigSkeleton} from "../src/topology/Configurator";
import {ICounterProcessInfo} from "../src/topology/counter/Counter";
import Pipes from "./../src/Pipes";

const testTopology: ITopologyConfigSkeleton = {
    name: "linear-topo-with-splitter",
    nodes: [
        {
            id: "node-a",
            resequencer: true,
            debug: {
                port: 4101,
                host: "localhost",
                url: "http://localhost:4101/status",
            },
            next: ["node-b"],
        },
        {
            id: "node-b",
            resequencer: true,
            worker: {
                type: "splitter.json",
                settings: {
                    node_id: "node-b",
                },
            },
            debug: {
                port: 4102,
                host: "localhost",
                url: "http://localhost:4102/status",
            },
            next: ["node-c"],
        },
        {
            id: "node-c",
            resequencer: true,
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
const firstQueue = `pipes.${testTopology.name}.${testTopology.nodes[0].id}`;

describe("Linear topology with splitter test", () => {
    it("complete flow of messages till the end", (done) => {
        const msgTestContent = {
            data: [
                { val : "to be split 1"},
                { val : "to be split 2"},
                { val : "to be split 3"},
                { val : "to be split 4"},
            ],
            settings: {},
        };
        const msgHeaders = { headers: { correlation_id: "corrid", process_id: "test", parent_id: "", sequence_id: 1 } };

        const pip = new Pipes(testTopology);

        Promise.all([
            pip.startCounter(),
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
                    assert.equal(data.id, msgHeaders.headers.process_id);
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
