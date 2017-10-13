import { assert } from "chai";
import "mocha";

import {Channel, Message} from "amqplib";
import Connection from "lib-nodejs/dist/src/rabbitmq/Connection";
import SimpleConsumer from "lib-nodejs/dist/src/rabbitmq/SimpleConsumer";
import {amqpConnectionOptions} from "../../../../src/config";
import Headers from "../../../../src/message/Headers";
import {PFHeaders} from "../../../../src/message/HeadersEnum";
import JobMessage from "../../../../src/message/JobMessage";
import {ResultCode} from "../../../../src/message/ResultCode";
import FollowersPublisher from "../../../../src/node/drain/amqp/FollowersPublisher";
import {IAmqpDrainSettings} from "../../../../src/node/drain/AmqpDrain";
import {INodeLabel} from "../../../../src/topology/Configurator";

const conn = new Connection(amqpConnectionOptions);
const settings: IAmqpDrainSettings = {
    node_label: {
        id: "test-counter-publisher",
        node_id: "507f191e810c19729de860ea",
        node_name: "counter",
    },
    counter: {
        queue: {
            name: "test-drain-counter",
            options: {},
        },
    },
    repeater: {
        queue: {
            name: "test-drain-repeater",
            options: {},
        },
    },
    faucet: {
        queue: {
            name: "repeat_queue",
            options: {},
        },
    },
    resequencer: false,
    // All followers targets the same exchange and queue with the same RK
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
        const node: INodeLabel = {id: "nodeId", node_id: "nodeId", node_name: "nodeName"};
        const receivedMessages: Message[] = [];
        const checkEnd = () => {
            if (receivedMessages.length === 3) {
                done();
            }
        };

        const fConfig = settings.followers[0];
        const outputQueue = fConfig.queue.name;
        const publisher = new FollowersPublisher(conn, settings);
        const msgCorrId = "corrId";
        const msgProcessId = "123";
        const msgParentId = "";
        const msgSeqId = 1;
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
                assert.deepEqual(
                    received.properties.headers,
                    {
                        pf_correlation_id: msgCorrId,
                        pf_process_id: msgProcessId,
                        pf_parent_id: msgParentId,
                        pf_sequence_id: `${msgSeqId}`,
                        pf_node_id: node.node_id,
                        pf_node_name: node.node_name,
                    },
                );
                checkEnd();
            },
        );

        consumer.consume(outputQueue, {})
            .then(() => {
                const headers = new Headers();
                headers.setPFHeader(PFHeaders.CORRELATION_ID, msgCorrId);
                headers.setPFHeader(PFHeaders.PROCESS_ID, msgProcessId);
                headers.setPFHeader(PFHeaders.PARENT_ID, "");
                headers.setPFHeader(PFHeaders.SEQUENCE_ID, `${msgSeqId}`);

                const msg: JobMessage = new JobMessage(
                    node,
                    headers.getRaw(),
                    new Buffer(JSON.stringify(msgBody)),
                    { code: ResultCode.SUCCESS, message: ""},
                );

                // This should send 3 messages
                publisher.send(msg);
            });
    });
});
