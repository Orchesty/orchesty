import { assert } from "chai";
import "mocha";

import {Channel, Message} from "amqplib";
import Connection from "lib-nodejs/dist/src/rabbitmq/Connection";
import SimpleConsumer from "lib-nodejs/dist/src/rabbitmq/SimpleConsumer";
import JobMessage from "../../../../src/message/JobMessage";
import FollowersPublisher from "../../../../src/node/drain/amqp/FollowersPublisher";
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
    // All followers targets the same exchange with the same RK
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
            routing_key: "drainRK",
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
            routing_key: "drainRK",
        },
        {
            node_id: "follower3",
            exchange: {
                name: "follower-queue",
                type: "direct",
                options: {},
            },
            queue: {
                name: "follower-queue",
                options: {},
            },
            routing_key: "drainRK",
        },
    ],
};

describe("FollowersPublisher", () => {
    it("publishes message to followers", (done) => {
        const receivedMessages: Message[] = [];
        const checkEnd = () => {
            if (receivedMessages.length === 3) {
                done();
            }
        };

        const outputQueue = "test-followers-queue";
        const fConfig = settings.followers[0];

        const publisher = new FollowersPublisher(conn, settings);
        const msgHeaders = { job_id: "123", sequence_id: 1};
        const msgBody = {data: "test", settings: {}};

        const consumer = new SimpleConsumer(
            conn,
            (ch: Channel): any => {
                return ch.assertQueue(outputQueue, {})
                    .then(() => {
                        return ch.purgeQueue(outputQueue);
                    })
                    .then(() => {
                        return ch.assertExchange(
                            fConfig.exchange.name,
                            fConfig.exchange.type,
                            fConfig.exchange.options,
                        );
                    })
                    .then(() => {
                        return ch.bindQueue(outputQueue, fConfig.exchange.name, fConfig.routing_key);
                    });
            },
            (received: Message) => {
                receivedMessages.push(received);
                // Check if content and headers remain the same
                assert.deepEqual(msgBody, JSON.parse(received.content.toString()));
                assert.deepEqual(msgHeaders, received.properties.headers);
                checkEnd();
            },
        );

        consumer.consume(outputQueue, {})
            .then(() => {
                const msg: JobMessage = new JobMessage(msgHeaders, JSON.stringify(msgBody));
                msg.setJobResultOK();
                // This should send 3 messages
                publisher.send(msg);
            });
    });
});
