import { assert } from "chai";
import "mocha";

import {Channel, Message, Options} from "amqplib";
import {Connection} from "amqplib-plus/dist/lib/Connection";
import {SimpleConsumer} from "amqplib-plus/dist/lib/SimpleConsumer";
import {amqpConnectionOptions} from "../../../../src/config";
import Headers from "../../../../src/message/Headers";
import JobMessage from "../../../../src/message/JobMessage";
import {ResultCode} from "../../../../src/message/ResultCode";
import CounterPublisher from "../../../../src/node/drain/amqp/CounterPublisher";
import {IAmqpDrainSettings} from "../../../../src/node/drain/AmqpDrain";
import {INodeLabel} from "../../../../src/topology/Configurator";

const conn = new Connection(amqpConnectionOptions);
const settings: IAmqpDrainSettings = {
    node_label: {
        id: "drainId",
        node_id: "someDrainId",
        node_name: "drainName",
        topology_id: "topId",
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
        const msgCorrId = "corrId";
        const msgProcessId = "123";
        const msgSeqId = 1;
        const msgBody = new Buffer(JSON.stringify({some: "json content"}));
        const node: INodeLabel = {id: "nodeId", node_id: "nodeId", node_name: "nodeName", topology_id: "topId"};

        const headers = new Headers();
        headers.setPFHeader(Headers.CORRELATION_ID, msgCorrId);
        headers.setPFHeader(Headers.PROCESS_ID, msgProcessId);
        headers.setPFHeader(Headers.PARENT_ID, "");
        headers.setPFHeader(Headers.SEQUENCE_ID, `${msgSeqId}`);

        const msg: JobMessage = new JobMessage(node, headers.getRaw(), msgBody);
        msg.setResult({ code: ResultCode.SUCCESS, message: ""});

        // Overrides the parental function to check the data being sent easily
        publisher.sendToQueue = (q: string, body: Buffer, opts: Options.Publish) => {
            return new Promise((resolve) => {
                assert.equal(q, settings.counter.queue.name);
                // In order to be able to test these random values
                opts.messageId = "fakeId";
                opts.timestamp = 10203040;
                assert.deepEqual(
                    opts,
                    {
                        headers: {
                            "pf-correlation-id": "corrId",
                            "pf-process-id": msgProcessId,
                            "pf-parent-id": "",
                            "pf-sequence-id": `${msgSeqId}`,
                            "pf-node-id": settings.node_label.node_id,
                            "pf-node-name": settings.node_label.node_name,
                            "pf-topology-id": settings.node_label.topology_id,
                        },
                        type: "counter_message",
                        appId: settings.node_label.id,
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
        const msgCorrId = "corrId";
        const msgProcessId = "123";
        const msgSeqId = 1;
        const msgBody = {data: "test", settings: {}};

        const headers = new Headers();
        headers.setPFHeader(Headers.CORRELATION_ID, msgCorrId);
        headers.setPFHeader(Headers.PROCESS_ID, msgProcessId);
        headers.setPFHeader(Headers.PARENT_ID, "");
        headers.setPFHeader(Headers.SEQUENCE_ID, `${msgSeqId}`);

        const consumer = new SimpleConsumer(
            conn,
            (ch: Channel): any => {
                return ch.assertQueue(settings.counter.queue.name, {})
                    .then(() => {
                        return ch.purgeQueue(settings.counter.queue.name);
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
        consumer.consume(settings.counter.queue.name, {})
            .then(() => {
                const node: INodeLabel = {id: "nodeId", node_id: "nodeId", node_name: "nodeName", topology_id: "topId"};
                const msg: JobMessage = new JobMessage(node, headers.getRaw(), new Buffer(JSON.stringify(msgBody)));
                msg.setResult({ code: ResultCode.SUCCESS, message: ""});

                publisher.send(msg);
            });
    });
});
