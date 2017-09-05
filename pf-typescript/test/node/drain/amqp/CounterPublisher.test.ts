import { assert } from "chai";
import "mocha";

import {Channel, Message} from "amqplib";
import Connection from "lib-nodejs/dist/src/rabbitmq/Connection";
import SimpleConsumer from "lib-nodejs/dist/src/rabbitmq/SimpleConsumer";
import JobMessage from "../../../../src/message/JobMessage";
import CounterPublisher from "../../../../src/node/drain/amqp/CounterPublisher";
import {IAMQPDrainSettings} from "../../../../src/node/drain/AMQPDrain";
import {testAmqpConnectionOptions} from "../../../config";

const conn = new Connection(testAmqpConnectionOptions);
const settings: IAMQPDrainSettings = {
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
        const msgHeaders = { job_id: "123", sequence_id: 1};
        const msgBody = JSON.stringify({data: "test", settings: {}});
        const msg: JobMessage = new JobMessage(msgHeaders, msgBody);
        msg.setJobResultOK();

        // Overrides the parental function to check the data being sent easily
        publisher.sendToQueue = (q: string, body: Buffer, opts: any) => {
            return new Promise((resolve) => {
                assert.equal(q, settings.counter_event.queue.name);
                assert.deepEqual(opts, { headers: { job_id: "123", node_id: settings.node_id}});
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
        const msgHeaders = { job_id: "123", sequence_id: 1};
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
                const msg: JobMessage = new JobMessage(msgHeaders, JSON.stringify(msgBody));
                msg.setJobResultOK();
                publisher.send(msg);
            });
    });
});
