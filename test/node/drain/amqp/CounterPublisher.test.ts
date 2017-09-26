import { assert } from "chai";
import "mocha";

import {Channel, Message, Options} from "amqplib";
import Connection from "lib-nodejs/dist/src/rabbitmq/Connection";
import SimpleConsumer from "lib-nodejs/dist/src/rabbitmq/SimpleConsumer";
import {amqpConnectionOptions} from "../../../../src/config";
import JobMessage from "../../../../src/message/JobMessage";
import {ResultCode} from "../../../../src/message/ResultCode";
import CounterPublisher from "../../../../src/node/drain/amqp/CounterPublisher";
import {IAmqpDrainSettings} from "../../../../src/node/drain/AmqpDrain";

const conn = new Connection(amqpConnectionOptions);
const settings: IAmqpDrainSettings = {
    node_id: "test-counter-publisher",
    counter_event: {
        queue: {
            name: "test-drain-counter",
            options: {},
        },
    },
    resequencer: false,
    followers: [
        {
            node_id: "follower1",
            exchange: {
                name: "follower-exchange",
                type: "direct",
                options: {},
            },
            queue: {
                name: "follower-queue",
                options: {},
            },
            routing_key: "drainRK1",
        },
        {
            node_id: "follower2",
            exchange: {
                name: "follower-exchange",
                type: "direct",
                options: {},
            },
            queue: {
                name: "follower-queue",
                options: {},
            },
            routing_key: "drainRK2",
        },
    ],
};

describe("CounterPublisher", () => {
    it("composes message in correct format", () => {
        const publisher = new CounterPublisher(conn, settings);
        const msgJobId = "123";
        const msgSeqId = 1;
        const msgHeaders = { job_id: msgJobId, sequence_id: msgSeqId.toString()};
        const msgBody = JSON.stringify({data: "test", settings: {}});
        const msg: JobMessage = new JobMessage(
            msgJobId,
            msgJobId,
            msgSeqId,
            msgHeaders,
            msgBody,
            { status: ResultCode.SUCCESS, message: ""},
        );

        // Overrides the parental function to check the data being sent easily
        publisher.sendToQueue = (q: string, body: Buffer, opts: Options.Publish) => {
            return new Promise((resolve) => {
                assert.equal(q, settings.counter_event.queue.name);
                // In order to be able to test these random values
                opts.messageId = "fakeId";
                opts.timestamp = 10203040;
                assert.deepEqual(
                    opts,
                    {
                        headers: { job_id: "123", node_id: settings.node_id},
                        type: "counter_message",
                        appId: settings.node_id,
                        messageId: "fakeId",
                        timestamp: 10203040,

            },
                );
                assert.deepEqual(
                    JSON.parse(body.toString()),
                    {
                        result: {
                            code: 0,
                            message: "",
                        },
                        route: {
                            following: 2,
                            multiplier: 1,
                        },
                    },
                );
                resolve();
            });
        };

        return publisher.send(msg);
    });
    it("publishes message to counter input queue", (done) => {
        const publisher = new CounterPublisher(conn, settings);
        const msgJobId = "123";
        const msgSeqId = 1;
        const msgHeaders = { job_id: msgJobId, sequence_id: msgSeqId.toString()};
        const msgBody = {data: "test", settings: {}};

        const consumer = new SimpleConsumer(
            conn,
            (ch: Channel): any => {
                return ch.assertQueue(settings.counter_event.queue.name, {})
                    .then(() => {
                        return ch.purgeQueue(settings.counter_event.queue.name);
                    });
            },
            (received: Message) => {
                assert.deepEqual(
                    JSON.parse(received.content.toString()),
                    {
                        result: {
                            code: 0,
                            message: "",
                        },
                        route: {
                            following: 2,
                            multiplier: 1,
                        },
                    },
                );
                done();
            },
        );
        consumer.consume(settings.counter_event.queue.name, {})
            .then(() => {
                const msg: JobMessage = new JobMessage(
                    msgJobId,
                    msgJobId,
                    msgSeqId,
                    msgHeaders,
                    JSON.stringify(msgBody),
                    { status: ResultCode.SUCCESS, message: ""},
                );
                publisher.send(msg);
            });
    });
});
