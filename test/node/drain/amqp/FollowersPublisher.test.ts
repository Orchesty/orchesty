import { assert } from "chai";
import "mocha";

import {Channel, Message} from "amqplib";
import {Connection} from "amqplib-plus/dist/lib/Connection";
import {SimpleConsumer} from "amqplib-plus/dist/lib/SimpleConsumer";
import {amqpConnectionOptions, persistentMessages} from "../../../../src/config";
import Headers from "../../../../src/message/Headers";
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
        topology_id: "topoId",
    },
    persistent: persistentMessages,
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
    it("publishes message to followers #integration", (done) => {
        const node: INodeLabel = {id: "nodeId", node_id: "nodeId", node_name: "nodeName", topology_id: "topoId"};
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
        const msgRootProcessId = "123";
        const msgParentId = "";
        const msgSeqId = 1;
        const msgBody = {data: "test", settings: {}};

        const consumer = new SimpleConsumer(
            conn,
            async (ch: Channel): Promise<any> => {
                await ch.assertQueue(outputQueue, {});
                await ch.purgeQueue(outputQueue);
                await ch.assertExchange(
                    fConfig.exchange.name,
                    fConfig.exchange.type,
                    fConfig.exchange.options,
                );
                await ch.bindQueue(outputQueue, fConfig.exchange.name, fConfig.routing_key);
            },
            (received: Message) => {
                receivedMessages.push(received);
                // Check if content and headers remain the same
                assert.deepEqual(msgBody, JSON.parse(received.content.toString()));

                const headers = new Headers(received.properties.headers);
                assert.equal(headers.getPFHeader(Headers.CORRELATION_ID), msgCorrId);
                assert.equal(headers.getPFHeader(Headers.PARENT_ID), msgRootProcessId);
                assert.equal(headers.getPFHeader(Headers.SEQUENCE_ID), `${msgSeqId}`);
                assert.equal(headers.getPFHeader(Headers.NODE_ID), node.node_id);
                assert.equal(headers.getPFHeader(Headers.NODE_NAME), node.node_name);
                assert.lengthOf(headers.getPFHeader(Headers.PROCESS_ID), 36); // some auto-generated uuid

                checkEnd();
            },
        );

        // When consumer is ready, call publisher's send method
        consumer.consume(outputQueue, {})
            .then(() => {
                const headers = new Headers();
                headers.setPFHeader(Headers.CORRELATION_ID, msgCorrId);
                headers.setPFHeader(Headers.PROCESS_ID, msgRootProcessId);
                headers.setPFHeader(Headers.PARENT_ID, msgParentId);
                headers.setPFHeader(Headers.SEQUENCE_ID, `${msgSeqId}`);

                const msg: JobMessage = new JobMessage(node, headers.getRaw(), Buffer.from(JSON.stringify(msgBody)));
                msg.setResult({ code: ResultCode.SUCCESS, message: ""});

                // This should send 3 messages setting parentId to processId (when splitting into more than 1 followers)
                publisher.send(msg);
            });
    });
});
