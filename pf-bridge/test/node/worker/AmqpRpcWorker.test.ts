import { assert } from "chai";
import "mocha";

import {Channel, Message, Options} from "amqplib";
import Connection from "amqplib-plus/dist/lib/Connection";
import Publisher from "amqplib-plus/dist/lib/Publisher";
import SimpleConsumer from "amqplib-plus/dist/lib/SimpleConsumer";
import {amqpConnectionOptions} from "../../../src/config";
import Headers from "../../../src/message/Headers";
import JobMessage from "../../../src/message/JobMessage";
import {ResultCode} from "../../../src/message/ResultCode";
import IPartialForwarder from "../../../src/node/drain/IPartialForwarder";
import AmqpRpcWorker, {IAmqpRpcWorkerSettings} from "../../../src/node/worker/AmqpRpcWorker";
import {INodeLabel} from "../../../src/topology/Configurator";

const conn = new Connection(amqpConnectionOptions);

describe("AmqpRpcWorker", () => {

    it("should check if worker is ready by sending rpc message", () => {
        const settings: IAmqpRpcWorkerSettings = {
            node_label: {
                id: "amqp_rpc_node_test",
                node_id: "507f191e810c19729de860ea",
                node_name: "amqprpcnode",
                topology_id: "topoId",
            },
            publish_queue: {
                name: "amqp_rpc_pub_test",
                options: {},
            },
        };
        const partialForwarder: IPartialForwarder = {
            forwardPart: () => Promise.resolve(),
        };
        const rpcWorker = new AmqpRpcWorker(conn, settings, partialForwarder);

        const publisher = new Publisher(conn, (ch: Channel) =>  Promise.resolve() );
        const externalWorkerMock = new SimpleConsumer(
            conn,
            (ch: Channel) => {
                return new Promise((resolve) => {
                    ch.assertQueue(settings.publish_queue.name, settings.publish_queue.options)
                        .then(() => {
                            return ch.purgeQueue(settings.publish_queue.name);
                        })
                        .then(() => {
                            resolve();
                        });
                });
            },
            (msg: Message) => {
                assert.typeOf(msg.properties.correlationId, "string");
                assert.lengthOf(msg.properties.correlationId, 36); // is some uuid
                assert.equal(
                    msg.properties.replyTo,
                    `pipes.${settings.node_label.topology_id}.${settings.node_label.id}_reply`,
                );
                assert.isTrue(Headers.containsAllMandatory(msg.properties.headers));

                const replyHeaders = new Headers(msg.properties.headers);
                replyHeaders.setPFHeader(Headers.RESULT_CODE, `${ResultCode.SUCCESS}`);
                replyHeaders.setPFHeader(Headers.RESULT_MESSAGE, "test ok");

                const options: Options.Publish = msg.properties;
                options.headers = replyHeaders.getRaw();

                publisher.sendToQueue(msg.properties.replyTo, msg.content, options);
            },
        );

        return externalWorkerMock.consume(settings.publish_queue.name, {})
            .then(() => {
                return rpcWorker.isWorkerReady();
            })
            .then((isReady: boolean) => {
                assert.isTrue(isReady);
            });
    });

    it("should send 1 message to external worker and receive multiple with proper headers", () => {
        const forwarded: JobMessage[] = [];
        const settings: IAmqpRpcWorkerSettings = {
            node_label: {
                id: "amqp_rpc_node_multiple",
                node_id: "507f191e810c19729de860ea",
                node_name: "amqprpcnode",
                topology_id: "topoId",
            },
            publish_queue: {
                name: "amqp_rpc_pub_multiple",
                options: {},
            },
        };
        const partialForwarder: IPartialForwarder = {
            forwardPart: (jm: JobMessage) => {
                forwarded.push(jm);
                return Promise.resolve();
            },
        };
        const rpcWorker = new AmqpRpcWorker(conn, settings, partialForwarder);
        const publisher = new Publisher(conn, (ch: Channel) =>  Promise.resolve() );
        const externalWorkerMock = new SimpleConsumer(
            conn,
            (ch: Channel) => {
                return new Promise((resolve) => {
                    ch.assertQueue(settings.publish_queue.name, settings.publish_queue.options)
                        .then(() => {
                            return ch.purgeQueue(settings.publish_queue.name);
                        })
                        .then(() => {
                            resolve();
                        });
                });
            },
            (msg: Message) => {
                // check if message has all expected headers
                assert.lengthOf(msg.properties.correlationId, 36); // uuidv4 length
                assert.equal(
                    msg.properties.replyTo,
                    `pipes.${settings.node_label.topology_id}.${settings.node_label.id}_reply`,
                );
                assert.isTrue(Headers.containsAllMandatory(msg.properties.headers));
                const headers = new Headers(msg.properties.headers);
                assert.equal(headers.getPFHeader(Headers.NODE_ID), "507f191e810c19729de860ea");
                assert.equal(headers.getPFHeader(Headers.NODE_NAME), "amqprpcnode");
                assert.equal(headers.getPFHeader(Headers.CORRELATION_ID), "amqp.worker.correlation_id");
                assert.equal(headers.getPFHeader(Headers.PROCESS_ID), "amqp.worker.process_id");
                assert.equal(headers.getPFHeader(Headers.PARENT_ID), "");
                assert.equal(headers.getPFHeader(Headers.SEQUENCE_ID), "1");
                assert.equal(headers.getPFHeader(Headers.TOPOLOGY_ID), "topoId");
                assert.equal(headers.getPFHeader(Headers.TOPOLOGY_NAME), "topoName");
                assert.equal(headers.getPFHeader("foo"), "bar");

                // Send partial messages and the confirmation message afterwards
                let i = 1;
                const proms = [];
                while (i <= 5) {
                    const replyHeaders = JSON.parse(JSON.stringify(msg.properties.headers));
                    replyHeaders["pf-sequence-id"] = i;
                    replyHeaders["pf-result-code"] = ResultCode.SUCCESS;
                    replyHeaders["pf-result-message"] = "ok";

                    const content = new Buffer(`${i}`);

                    const p = publisher.sendToQueue(
                        msg.properties.replyTo,
                        content,
                        {
                            type: AmqpRpcWorker.BATCH_ITEM_TYPE,
                            correlationId: msg.properties.correlationId,
                            headers: replyHeaders,
                        },
                    );
                    proms.push(p);
                    i++;
                }

                // Send confirmation message after all BATCH_ITEM messages are sent
                Promise.all(proms)
                    .then(() => {
                        const finalHeaders = JSON.parse(JSON.stringify(msg.properties.headers));
                        finalHeaders["pf-sequence-id"] = 1;
                        finalHeaders["pf-result-code"] = ResultCode.SUCCESS;
                        finalHeaders["pf-result-message"] = "everything okay";

                        publisher.sendToQueue(
                            msg.properties.replyTo,
                            new Buffer(""),
                            {
                                type: AmqpRpcWorker.BATCH_END_TYPE,
                                correlationId: msg.properties.correlationId,
                                headers: finalHeaders,
                            },
                        );
                    });
            },
        );

        return externalWorkerMock.consume(settings.publish_queue.name, {})
            .then(() => {
                const node: INodeLabel = {
                    id: "amqp.worker.node_id",
                    node_id: "nodeId",
                    node_name: "nodeName",
                    topology_id: "topoId",
                };
                const headers = new Headers();
                headers.setPFHeader(Headers.CORRELATION_ID, "amqp.worker.correlation_id");
                headers.setPFHeader(Headers.PROCESS_ID, "amqp.worker.process_id");
                headers.setPFHeader(Headers.PARENT_ID, "");
                headers.setPFHeader(Headers.SEQUENCE_ID, "1");
                headers.setPFHeader(Headers.TOPOLOGY_ID, "topoId");
                headers.setPFHeader(Headers.TOPOLOGY_NAME, "topoName");
                headers.setPFHeader("foo", "bar");
                headers.setHeader("baz", "bat");

                const jobMsg = new JobMessage(
                    node,
                    headers.getRaw(),
                    new Buffer(JSON.stringify({ settings: {}, data: "test" })),
                );

                return rpcWorker.processData(jobMsg);
            })
            .then((outMsgs: JobMessage[]) => {
                assert.lengthOf(outMsgs, 1);
                const outMsg: JobMessage = outMsgs[0];

                assert.instanceOf(outMsg, JobMessage);
                assert.equal(outMsg.getMultiplier(), 5);
                assert.isFalse(outMsg.getForwardSelf());
                assert.equal(ResultCode.SUCCESS, outMsg.getResult().code);
                assert.equal("everything okay", outMsg.getResult().message);

                let i = 1;
                forwarded.forEach((splitMsg: JobMessage) => {
                    assert.equal(i, splitMsg.getSequenceId());
                    assert.equal(`${i}`, splitMsg.getContent());
                    i++;
                });
            });
    });
});
