import { assert } from "chai";
import "mocha";

import {Channel, Message} from "amqplib";
import Connection from "lib-nodejs/dist/src/rabbitmq/Connection";
import Publisher from "lib-nodejs/dist/src/rabbitmq/Publisher";
import SimpleConsumer from "lib-nodejs/dist/src/rabbitmq/SimpleConsumer";
import {amqpConnectionOptions} from "../../../src/config";
import Headers from "../../../src/message/Headers";
import {PFHeaders} from "../../../src/message/HeadersEnum";
import JobMessage from "../../../src/message/JobMessage";
import {ResultCode} from "../../../src/message/ResultCode";
import IPartialForwarder from "../../../src/node/drain/IPartialForwarder";
import AmqpRpcWorker, {IAmqpRpcWorkerSettings} from "../../../src/node/worker/AmqpRpcWorker";
import {BATCH_END_TYPE, BATCH_ITEM_TYPE} from "../../../src/node/worker/AmqpRpcWorker";
import {INodeLabel} from "../../../src/topology/Configurator";

const conn = new Connection(amqpConnectionOptions);

describe("AmqpRpcWorker", () => {
    it("should check if worker is ready by sending rpc message", () => {
        const forwarded: JobMessage[] = [];
        const settings: IAmqpRpcWorkerSettings = {
            node_label: {
                id: "amqp_rpc_node_test",
                node_id: "507f191e810c19729de860ea",
                node_name: "amqprpcnode",
            },
            publish_queue: {
                name: "amqp_rpc_pub_test",
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
                // Send the received message to reply queue
                publisher.sendToQueue(msg.properties.replyTo, msg.content, msg.properties);
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

    it("should send 1 message to external worker and receive multiple", () => {
        const forwarded: JobMessage[] = [];
        const settings: IAmqpRpcWorkerSettings = {
            node_label: {
                id: "amqp_rpc_node_multiple",
                node_id: "507f191e810c19729de860ea",
                node_name: "amqprpcnode",
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
                assert.deepEqual(msg.properties.headers, {
                    "pf-node-id": "507f191e810c19729de860ea",
                    "pf-node-name": "amqprpcnode",
                    "pf-correlation-id": "amqp.worker.correlation_id",
                    "pf-process-id": "amqp.worker.process_id",
                    "pf-parent-id": "",
                    "pf-sequence-id": "1",
                    "pf-topology-id": "topoId",
                    "pf-topology-name": "topoName",
                    "pf-foo": "bar",
                });

                // Send partial messages and the confirmation message afterwards
                let i = 1;
                const proms = [];
                while (i <= 5) {
                    const replyHeaders = JSON.parse(JSON.stringify(msg.properties.headers));
                    replyHeaders["pf-sequence-id"] = i;
                    replyHeaders["pf-result-code"] = ResultCode.SUCCESS;
                    replyHeaders["pf-result-message"] = "ok";

                    const p = publisher.sendToQueue(
                        msg.properties.replyTo,
                        new Buffer(JSON.stringify({ settings: {}, data: `${i}`})),
                        {
                            type: BATCH_ITEM_TYPE,
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
                                type: BATCH_END_TYPE,
                                correlationId: msg.properties.correlationId,
                                headers: finalHeaders,
                            },
                        );
                    });
            },
        );

        return externalWorkerMock.consume(settings.publish_queue.name, {})
            .then(() => {
                const node: INodeLabel = {id: "amqp.worker.node_id", node_id: "nodeId", node_name: "nodeName"};
                const headers = new Headers();
                headers.setPFHeader(PFHeaders.CORRELATION_ID, "amqp.worker.correlation_id");
                headers.setPFHeader(PFHeaders.PROCESS_ID, "amqp.worker.process_id");
                headers.setPFHeader(PFHeaders.PARENT_ID, "");
                headers.setPFHeader(PFHeaders.SEQUENCE_ID, "1");
                headers.setPFHeader(PFHeaders.TOPOLOGY_ID, "topoId");
                headers.setPFHeader(PFHeaders.TOPOLOGY_NAME, "topoName");
                headers.setPFHeader("foo", "bar");
                headers.setHeader("baz", "bat");

                const jobMsg = new JobMessage(
                    node,
                    headers.getRaw(),
                    new Buffer(JSON.stringify({ settings: {}, data: "test" })),
                );

                return rpcWorker.processData(jobMsg);
            })
            .then((outMsg: JobMessage) => {
                assert.instanceOf(outMsg, JobMessage);
                assert.equal(outMsg.getMultiplier(), 5);
                assert.isFalse(outMsg.getForwardSelf());
                assert.equal(ResultCode.SUCCESS, outMsg.getResult().code);
                assert.equal("everything okay", outMsg.getResult().message);

                let i = 1;
                forwarded.forEach((splitMsg: JobMessage) => {
                    assert.equal(i, splitMsg.getSequenceId());

                    const body = JSON.parse(splitMsg.getContent());
                    assert.equal(i, parseInt(body.data, 10));
                    i++;
                });
            });
    });
});
