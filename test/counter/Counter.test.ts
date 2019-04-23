import {assert} from "chai";
import "mocha";

import {Channel, Message} from "amqplib";
import {Connection} from "amqplib-plus/dist/lib/Connection";
import {Publisher} from "amqplib-plus/dist/lib/Publisher";
import {SimpleConsumer} from "amqplib-plus/dist/lib/SimpleConsumer";
import {amqpConnectionOptions, persistentQueues, redisStorageOptions} from "../../src/config";
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

const runCounterTest = async (counter: Counter, testOutputQueue: any, done: any) => {
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
                    "pf-correlation-id": "corrid_123",
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
                    "pf-correlation-id": "corrid_123",
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
                    "pf-correlation-id": "corrid_123",
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
                    "pf-correlation-id": "corrid_456",
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
                    "pf-correlation-id": "corrid_456",
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
                    "pf-correlation-id": "corrid_456",
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
                    "pf-correlation-id": "corrid_789",
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
                    "pf-correlation-id": "corrid_789",
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
                    "pf-correlation-id": "corrid_789",
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
                    "pf-correlation-id": "corrid_789",
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
                assert.equal(info.total, 1);
                assert.equal(info.ok, 1);
                assert.equal(info.nok, 0);
                assert.isTrue(info.success);
                assert.lengthOf(info.messages, 1);
                assert.property(info, "correlation_id");
                assert.property(info, "start_timestamp");
                assert.property(info, "end_timestamp");
                break;
            case "test_job_456":
                assert.equal(info.total, 2);
                assert.equal(info.ok, 1);
                assert.equal(info.nok, 1);
                assert.isFalse(info.success);
                assert.lengthOf(info.messages, 2);
                assert.property(info, "correlation_id");
                assert.property(info, "start_timestamp");
                assert.property(info, "end_timestamp");
                break;
            case "test_job_789":
                assert.equal(info.total, 3);
                assert.equal(info.ok, 3);
                assert.equal(info.nok, 0);
                assert.isTrue(info.success);
                assert.lengthOf(info.messages, 3);
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
        return new Promise(async (resolve) => {
            await ch.assertQueue(counter.getSettings().sub.queue.name, counter.getSettings().sub.queue.options);
            await ch.purgeQueue(counter.getSettings().sub.queue.name);
            resolve();
        });
    };
    const publisher = new Publisher(conn, preparePublisher);
    const prepareConsumer = (ch: Channel): Promise<void> => {
        return new Promise(async (resolve) => {
            const q = await ch.assertQueue(testOutputQueue.name, { durable: persistentQueues });
            await ch.bindQueue(
                q.queue,
                counter.getSettings().pub.exchange.name,
                counter.getSettings().pub.routing_key,
            );
            await ch.purgeQueue(testOutputQueue.name);
            resolve();
        });
    };
    // In this moment test can be evaluated
    const handleMessage = (msg: Message) => {
        const result: ICounterProcessInfo = JSON.parse(msg.content.toString());
        evaluateTest(result);
    };
    const consumer = new SimpleConsumer(conn, prepareConsumer, handleMessage);
    consumer.consume(testOutputQueue.name, testOutputQueue.options);

    await counter.start();
    const promises: Array<Promise<any>> = [];
    events.forEach((ev) => {
        promises.push(
            publisher.sendToQueue(
                counter.getSettings().sub.queue.name,
                Buffer.from(JSON.stringify(ev[0])),
                ev[1],
            ),
        );
    });

    await Promise.all(promises);
};

describe("Counter", () => {

    it("using inMemory storage should evaluate processes properly #integration", (done) => {
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

    it("using RedisStorage storage should evaluate processes properly #integration", (done) => {
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

    it("handleMessage method should return rejection on invalid message #integration", async () => {
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

        const emptyFields: any = {};
        const emptyProps: any = {};

        try {
            const msg: Message = {content: Buffer.from(""), fields: emptyFields, properties: emptyProps};
            // tslint:disable-next-line
            await counter["handleMessage"](msg);
        } catch (e) {
            assert.equal(e.message, "Unexpected end of JSON input");
        }

        try {
            const msg: Message = {content: Buffer.from('{"foo": "bar"}'), fields: emptyFields, properties: emptyProps};
            // tslint:disable-next-line
            await counter["handleMessage"](msg);
        } catch (e) {
            assert.equal(e.message, "Cannot read property \'code\' of undefined");
        }

        try {
            const content = JSON.stringify({result: {code: 0, message: ""}, route: {following: 1, multiplier: 1}});
            const msg: Message = {content: Buffer.from(content), fields: emptyFields, properties: emptyProps};
            // tslint:disable-next-line
            await counter["handleMessage"](msg);
        } catch (e) {
            assert.include(e.message, "headers");
        }
    });
});
