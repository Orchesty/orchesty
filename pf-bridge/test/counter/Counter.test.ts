import {assert} from "chai";
import "mocha";

import {Channel, Message} from "amqplib";
import {Connection} from "amqplib-plus/dist/lib/Connection";
import {Publisher} from "amqplib-plus/dist/lib/Publisher";
import {SimpleConsumer} from "amqplib-plus/dist/lib/SimpleConsumer";
import {Replies} from "amqplib/properties";
import {amqpConnectionOptions, redisStorageOptions} from "../../src/config";
import {default as Counter, ICounterSettings} from "../../src/counter/Counter";
import {ICounterProcessInfo} from "../../src/counter/CounterProcess";
import Distributor from "../../src/counter/distributor/Distributor";
import InMemoryStorage from "../../src/counter/storage/InMemoryStorage";
import RedisStorage from "../../src/counter/storage/RedisStorage";
import {ResultCode} from "../../src/message/ResultCode";
import Terminator from "../../src/terminator/Terminator";

const conn = new Connection(amqpConnectionOptions);
const metricsMock = {
    send: () => Promise.resolve("sent"),
    addTag: () => { return; },
    removeTag: () => { return; },
};

function runCounterTest(counter: Counter, testOutputQueue: any, done: any) {
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
                    "pf-topology-id": "topoid",
                    "pf-correlation-id": "corrid1",
                    "pf-process-id": "test_job_123",
                    "pf-parent-id": "",
                    "pf-sequence-id": "1",
                    "pf-node-id": "test_node_1",
                    "pf-node-name": "test_node_name_1",
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
                    "pf-topology-id": "topoid",
                    "pf-correlation-id": "corrid2",
                    "pf-process-id": "test_job_123",
                    "pf-parent-id": "",
                    "pf-sequence-id": "1",
                    "pf-node-id": "test_node_2",
                    "pf-node-name": "test_node_name_2",
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
                    "pf-topology-id": "topoid",
                    "pf-correlation-id": "corrid3",
                    "pf-process-id": "test_job_123",
                    "pf-parent-id": "",
                    "pf-sequence-id": "1",
                    "pf-node-id": "test_node_3",
                    "pf-node-name": "test_node_name_3",
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
                    "pf-topology-id": "topoid",
                    "pf-correlation-id": "corrid1",
                    "pf-process-id": "test_job_456",
                    "pf-parent-id": "",
                    "pf-sequence-id": "1",
                    "pf-node-id": "test_node_1",
                    "pf-node-name": "test_node_name_1",
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
                    "pf-topology-id": "topoid",
                    "pf-correlation-id": "corrid2",
                    "pf-process-id": "test_job_456",
                    "pf-parent-id": "",
                    "pf-sequence-id": "1",
                    "pf-node-id": "test_node_2",
                    "pf-node-name": "test_node_name_2",
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
                    "pf-topology-id": "topoid",
                    "pf-correlation-id": "corrid3",
                    "pf-process-id": "test_job_456",
                    "pf-parent-id": "",
                    "pf-sequence-id": "1",
                    "pf-node-id": "test_node_3",
                    "pf-node-name": "test_node_name_3",
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
                    "pf-topology-id": "topoid",
                    "pf-correlation-id": "corrid1",
                    "pf-process-id": "test_job_789",
                    "pf-parent-id": "",
                    "pf-sequence-id": "1",
                    "pf-node-id": "test_node_1",
                    "pf-node-name": "test_node_name_1",
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
                    "pf-topology-id": "topoid",
                    "pf-correlation-id": "corrid2",
                    "pf-process-id": "test_job_789",
                    "pf-parent-id": "",
                    "pf-sequence-id": "1",
                    "pf-node-id": "test_node_2",
                    "pf-node-name": "test_node_name_2",
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
                    "pf-topology-id": "topoid",
                    "pf-correlation-id": "corrid3",
                    "pf-process-id": "test_job_789",
                    "pf-parent-id": "",
                    "pf-sequence-id": "1",
                    "pf-node-id": "test_node_3",
                    "pf-node-name": "test_node_name_3",
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
                    "pf-topology-id": "topoid",
                    "pf-correlation-id": "corrid4",
                    "pf-process-id": "test_job_789",
                    "pf-parent-id": "",
                    "pf-sequence-id": "1",
                    "pf-node-id": "test_node_4",
                    "pf-node-name": "test_node_name_4",
                },
            },
        ],
    ];

    let resultsReceived = 0;
    const evaluateTest = (info: ICounterProcessInfo) => {
        switch (info.process_id) {
            case "test_job_123":
                assert.equal(info.total, 3);
                assert.equal(info.ok, 3);
                assert.equal(info.nok, 0);
                assert.isTrue(info.success);
                assert.lengthOf(info.messages, 3);
                assert.property(info, "correlation_id");
                assert.property(info, "start_timestamp");
                assert.property(info, "end_timestamp");
                break;
            case "test_job_456":
                assert.equal(info.total, 3);
                assert.equal(info.ok, 2);
                assert.equal(info.nok, 1);
                assert.isFalse(info.success);
                assert.lengthOf(info.messages, 3);
                assert.property(info, "correlation_id");
                assert.property(info, "start_timestamp");
                assert.property(info, "end_timestamp");
                break;
            case "test_job_789":
                assert.equal(info.total, 4);
                assert.equal(info.ok, 4);
                assert.equal(info.nok, 0);
                assert.isTrue(info.success);
                assert.lengthOf(info.messages, 4);
                assert.property(info, "correlation_id");
                assert.property(info, "start_timestamp");
                assert.property(info, "end_timestamp");
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
            ch.assertQueue(counter.getSettings().sub.queue.name, counter.getSettings().sub.queue.options)
                .then(() => {
                    return ch.purgeQueue(counter.getSettings().sub.queue.name);
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
                        counter.getSettings().pub.exchange.name,
                        counter.getSettings().pub.routing_key,
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

    counter.start()
        .then(() => {
            const promises: Array<Promise<any>> = [];
            events.forEach((ev) => {
                promises.push(
                    publisher.sendToQueue(
                        counter.getSettings().sub.queue.name,
                        new Buffer(JSON.stringify(ev[0])),
                        ev[1],
                    ),
                );
            });

            return Promise.all(promises);
        });
}

describe("Counter", () => {

    it("using inMemory storage should evaluate processes properly", (done) => {
        const counterSettings: ICounterSettings = {
            sub: {queue: {name: "test_counter_memory_sub_q", prefetch: 1, options: {}}},
            pub: {
                exchange: {name: "test_counter_memory_pub_e", type: "direct", options: {}},
                queue: {name: "test_counter_memory_pub_q", options: {}},
                routing_key: "pub_memory_rk",
            },
        };
        const testOutputQueue = {
            name: "test_counter_memory_output",
            options: {},
        };

        const storage = new InMemoryStorage();
        const terminator = new Terminator(7955, storage);
        const distributor = new Distributor();
        const counter = new Counter(counterSettings, conn, storage, distributor, terminator, metricsMock);
        runCounterTest(counter, testOutputQueue, done);
    });

    it("using RedisStorage storage should evaluate processes properly", (done) => {
        const counterSettings: ICounterSettings = {
            sub: {queue: {name: "test_counter_redis_sub_q", prefetch: 1, options: {}}},
            pub: {
                exchange: {name: "test_counter_redis_pub_e", type: "direct", options: {}},
                queue: {name: "test_counter_redis_pub_q", options: {}},
                routing_key: "pub_redis_rk",
            },
        };
        const testOutputQueue = {
            name: "test_counter_redis_output",
            options: {},
        };

        const storage = new RedisStorage(redisStorageOptions);
        const terminator = new Terminator(7956, storage);
        const distributor = new Distributor();
        const counter = new Counter(counterSettings, conn, storage, distributor, terminator, metricsMock);
        runCounterTest(counter, testOutputQueue, done);
    });

    it("handleMessage method should return rejection on invalid message", async () => {
        const counterSettings: ICounterSettings = {
            sub: {queue: {name: "test_counter_redis_sub_q", prefetch: 1, options: {}}},
            pub: {
                exchange: {name: "test_counter_redis_pub_e", type: "direct", options: {}},
                queue: {name: "test_counter_redis_pub_q", options: {}},
                routing_key: "pub_redis_rk",
            },
        };
        const storage = new InMemoryStorage();
        const terminator = new Terminator(7957, storage);
        const distributor = new Distributor();
        const counter = new Counter(counterSettings, conn, storage, distributor, terminator, metricsMock);

        try {
            const msg: Message = {content: new Buffer(""), fields: {}, properties: {}};
            // tslint:disable-next-line
            await counter["handleMessage"](msg);
        } catch (e) {
            assert.equal(e.message, "Unexpected end of JSON input");
        }

        try {
            const msg: Message = {content: new Buffer('{"foo": "bar"}'), fields: {}, properties: {}};
            // tslint:disable-next-line
            await counter["handleMessage"](msg);
        } catch (e) {
            assert.equal(e.message, "Cannot read property \'code\' of undefined");
        }

        try {
            const content = {result: {code: 0, message: ""}, route: {following: 1, multiplier: 1}};
            const msg: Message = {content: new Buffer(JSON.stringify(content)), fields: {}, properties: {}};
            // tslint:disable-next-line
            await counter["handleMessage"](msg);
        } catch (e) {
            assert.include(e.message, "headers");
        }
    });
});
