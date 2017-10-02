import { assert } from "chai";
import "mocha";

import {Channel, Message} from "amqplib";
import {Replies} from "amqplib/properties";
import logger from "lib-nodejs/dist/src/logger/Logger";
import Connection from "lib-nodejs/dist/src/rabbitmq/Connection";
import Publisher from "lib-nodejs/dist/src/rabbitmq/Publisher";
import SimpleConsumer from "lib-nodejs/dist/src/rabbitmq/SimpleConsumer";
import {amqpConnectionOptions} from "../../../src/config";
import {ResultCode} from "../../../src/message/ResultCode";
import {default as Counter, ICounterProcessInfo} from "../../../src/topology/counter/Counter";

const conn = new Connection(amqpConnectionOptions);
const counterSettings = {
    topology: "topoId",
    sub: {queue: {name: "test_counter_sub_q", prefetch: 1, options: {}}},
    pub: {
        exchange: {name: "test_counter_pub_e", type: "direct", options: {}},
        queue: {name: "test_counter_pub_q", options: {}},
        routing_key: "pub_rk",
    },
};
const testOutputQueue = {
    name: "test_counter_output",
    options: {},
};

const metricsMock = {
    send: () => Promise.resolve("sent"),
};

describe("Counter", () => {
    it("should receive messages and count them properly when all succeeded", (done) => {
        const events: Array<[{}, {}]> = [
            // Test Job 123 - linear success
            //
            //  SUCCESS - SUCCESS - SUCCESS
            //
            [
                {
                    result: {
                        code: ResultCode.SUCCESS,
                    },
                    route: {
                        following: 1,
                        multiplier: 1,
                        message: "test",
                    },
                },
                {
                    headers: {
                        process_id: "test_job_123",
                        node_id: "test_node_1",
                        correlation_id: "corrid1",
                        parent_id: "",
                    },
                },
            ],
            [
                {
                    result: {
                        code: ResultCode.SUCCESS,
                    },
                    route: {
                        following: 1,
                        multiplier: 1,
                        message: "test",
                    },
                },
                {
                    headers: {
                        process_id: "test_job_123",
                        node_id: "test_node_2",
                        correlation_id: "corrid1",
                        parent_id: "",
                    },
                },
            ],
            [
                {
                    result: {
                        code: ResultCode.SUCCESS,
                    },
                    route: {
                        following: 0,
                        multiplier: 1,
                        message: "test",
                    },
                },
                {
                    headers: {
                        process_id: "test_job_123",
                        node_id: "test_node_3",
                        correlation_id: "corrid1",
                        parent_id: "",
                    },
                },
            ],
            // Test Job 456 - linear with error
            //
            //  SUCCESS - FAILED - SUCCESS
            //
            [
                {
                    result: {
                        code: ResultCode.SUCCESS,
                    },
                    route: {
                        following: 1,
                        multiplier: 1,
                        message: "test",
                    },
                },
                {
                    headers: {
                        process_id: "test_job_456",
                        node_id: "test_node_1",
                        correlation_id: "corrid1",
                        parent_id: "",
                    },
                },
            ],
            [
                {
                    result: {
                        code: ResultCode.UNKNOWN_ERROR,
                    },
                    route: {
                        following: 1,
                        multiplier: 1,
                        message: "test",
                    },
                },
                {
                    headers: {
                        process_id: "test_job_456",
                        node_id: "test_node_2",
                        correlation_id: "corrid1",
                        parent_id: "",
                    },
                },
            ],
            [
                {
                    result: {
                        code: ResultCode.SUCCESS,
                    },
                    route: {
                        following: 0,
                        multiplier: 1,
                        message: "test",
                    },
                },
                {
                    headers: {
                        process_id: "test_job_456",
                        node_id: "test_node_2",
                        correlation_id: "corrid1",
                        parent_id: "",
                    },
                },
            ],
            // Test Job 789 - split success
            //
            //           SUCCESS
            //         /
            // SUCCESS
            //         \
            //           SUCCESS - SUCCESS
            [
                {
                    result: {
                        code: ResultCode.SUCCESS,
                    },
                    route: {
                        following: 2,
                        multiplier: 1,
                        message: "test",
                    },
                },
                {
                    headers: {
                        process_id: "test_job_789",
                        node_id: "test_node_1",
                        correlation_id: "corrid1",
                        parent_id: "",
                    },
                },
            ],
            [
                {
                    result: {
                        code: ResultCode.SUCCESS,
                    },
                    route: {
                        following: 0,
                        multiplier: 1,
                        message: "test",
                    },
                },
                {
                    headers: {
                        process_id: "test_job_789",
                        node_id: "test_node_2",
                        correlation_id: "corrid1",
                        parent_id: "",
                    },
                },
            ],
            [
                {
                    result: {
                        code: ResultCode.SUCCESS,
                    },
                    route: {
                        following: 1,
                        multiplier: 1,
                        message: "test",
                    },
                },
                {
                    headers: {
                        process_id: "test_job_789",
                        node_id: "test_node_3",
                        correlation_id: "corrid1",
                        parent_id: "",
                    },
                },
            ],
            [
                {
                    result: {
                        code: ResultCode.SUCCESS,
                    },
                    route: {
                        following: 0,
                        multiplier: 1,
                        message: "test",
                    },
                },
                {
                    headers: {
                        process_id: "test_job_789",
                        node_id: "test_node_4",
                        correlation_id: "corrid1",
                        parent_id: "",
                    },
                },
            ],
        ];

        let resultsReceived = 0;
        const evaluateTest = (info: ICounterProcessInfo) => {
            logger.info("Result message received", info);

            switch (info.process_id) {
                case "test_job_123":
                    assert.equal(info.total, 3);
                    assert.equal(info.ok, 3);
                    assert.equal(info.nok, 0);
                    break;
                case "test_job_456":
                    assert.equal(info.total, 3);
                    assert.equal(info.ok, 2);
                    assert.equal(info.nok, 1);
                    break;
                case "test_job_789":
                    assert.equal(info.total, 4);
                    assert.equal(info.ok, 4);
                    assert.equal(info.nok, 0);
                    break;
                default:
                    throw new Error(`Unexpected result for job_id: ${info.process_id}`);
            }

            resultsReceived++;
            if (resultsReceived === 3) {
                done();
            }
        };

        const preparePublisher = (ch: Channel): Promise<void> => {
            return new Promise((resolve) => {
                ch.assertQueue(counterSettings.sub.queue.name, counterSettings.sub.queue.options)
                    .then(() => {
                        return ch.purgeQueue(counterSettings.sub.queue.name);
                    })
                    .then(() => {
                        resolve();
                    });
            });
        };
        const publisher = new Publisher(conn, preparePublisher);
        const prepareConsumer = (ch: Channel): Promise<void> => {
            return new Promise((resolve) => {
                ch.assertQueue(testOutputQueue.name, {})
                    .then((q: Replies.AssertQueue) => {
                        return ch.bindQueue(
                            q.queue,
                            counterSettings.pub.exchange.name,
                            counterSettings.pub.routing_key,
                        );
                    })
                    .then(() => {
                        return ch.purgeQueue(testOutputQueue.name);
                    })
                    .then(() => {
                        resolve();
                    });
            });
        };
        // In this moment test can be evaluated
        const handleMessage = (msg: Message) => {
            const result: ICounterProcessInfo = JSON.parse(msg.content.toString());
            evaluateTest(result);
        };
        const consumer = new SimpleConsumer(conn, prepareConsumer, handleMessage);
        consumer.consume(testOutputQueue.name, testOutputQueue.options);

        const counter = new Counter(counterSettings, conn, metricsMock);
        counter.listen()
            .then(() => {
                const promises: Array<Promise<any>> = [];
                events.forEach((ev) => {
                    promises.push(
                        publisher.sendToQueue(counterSettings.sub.queue.name, new Buffer(JSON.stringify(ev[0])), ev[1]),
                    );
                    logger.info("Node message published.", JSON.stringify(ev[1]), JSON.stringify(ev[0]));
                });

                return Promise.all(promises);
            })
            .then(() => {
                logger.info("All event messages published.");
            });
    });
});
