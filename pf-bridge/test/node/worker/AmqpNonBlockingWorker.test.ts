import { assert } from "chai";
import "mocha";

import {Channel, Message, Options} from "amqplib";
import {Connection, Publisher} from "amqplib-plus";
import {amqpConnectionOptions} from "../../../src/config";
import Headers from "../../../src/message/Headers";
import JobMessage from "../../../src/message/JobMessage";
import {ResultCode} from "../../../src/message/ResultCode";
import {ICounterPublisher} from "../../../src/node/drain/amqp/CounterPublisher";
import IPartialForwarder from "../../../src/node/drain/IPartialForwarder";
import {IAmqpWorkerSettings, IWaiting} from "../../../src/node/worker/AAmqpWorker";
import AmqpNonBlockingWorker from "../../../src/node/worker/AmqpNonBlockingWorker";
import {INodeLabel} from "../../../src/topology/Configurator";
import {SimpleConsumer} from "../../../src/consumer/SimpleConsumer";
import {IPublisher} from "amqplib-plus/dist/IPublisher";

const conn = new Connection(amqpConnectionOptions);

describe("AmqpNonBlockingWorker", () => {

    it("should ignore batch item if not found in waiting list or hasn't success result #integration", async () => {
        const settings: IAmqpWorkerSettings = {
            node_label: {
                id: "amqp_rpc_unit_ignore",
                node_id: "507f191e810c19729de860ea",
                node_name: "ignore",
                topology_id: "topoId",
            },
            publish_queue: {
                name: "amqp_rpc_pub_test",
                options: {},
            },
        };
        const redis: any = {
            isProcessed() {return false},
            setProcessed() {return},
        };
        const forwarder: IPartialForwarder = {
            forwardPart: async () => { assert.fail("This should be never called."); },
        };
        const counterPublisher: ICounterPublisher = {
            send: async () => { assert.fail("This should be never called."); },
        };
        const assertionPublisher: IPublisher = {
            sendToQueue: ():  Promise<void> => { assert.fail("This should be never called."); },
            publish: ():  Promise<void> => { assert.fail("This should be never called."); },
        };
        const rpcWorker = new AmqpNonBlockingWorker(conn, settings, redis, forwarder, counterPublisher, assertionPublisher);

        const headers = new Headers();
        headers.setPFHeader(Headers.RESULT_CODE, `${ResultCode.UNKNOWN_ERROR}`);
        headers.setPFHeader(Headers.CORRELATION_ID, "aaa");
        headers.setPFHeader(Headers.PROCESS_ID, "aaa");
        headers.setPFHeader(Headers.PARENT_ID, "");
        headers.setPFHeader(Headers.SEQUENCE_ID, "0");
        const batchItemMsg: any = { content: Buffer.from(""), fields: {}, properties: {headers: headers.getRaw()} };
        await rpcWorker.onBatchItem("corr123", batchItemMsg);

        // hack using any type in order to allow add to waiting list even though it is private
        const hackedRpcWorker: any = rpcWorker;
        const jobMessage = new JobMessage(settings.node_label, headers.getRaw(), Buffer.from(""));
        const wait: IWaiting = { resolveFn: (): void  => null, message: jobMessage, sequence: 0};
        hackedRpcWorker.waiting.set("corr123", wait);

        await rpcWorker.onBatchItem("corr123", batchItemMsg);
    });

    it("worker should check if worker is ready by sending rpc message #integration", async () => {
        const settings: IAmqpWorkerSettings = {
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
        const redis: any = {
            isProcessed() {return false},
            setProcessed() {return},
        };
        const partialForwarder: IPartialForwarder = {
            forwardPart: () => Promise.resolve(),
        };
        const counterPublisher: ICounterPublisher = {
            send: async () => Promise.resolve(),
        };
        const assertionPublisher: IPublisher = {
            sendToQueue: ():  Promise<void> => { assert.fail("This should be never called."); },
            publish: ():  Promise<void> => { assert.fail("This should be never called."); },
        };
        const rpcWorker = new AmqpNonBlockingWorker(conn, settings, redis, partialForwarder, counterPublisher, assertionPublisher);

        const publisher = new Publisher(conn, (ch: Channel) =>  Promise.resolve() );
        const externalWorkerMock = new SimpleConsumer(
            conn,
            (ch: Channel) => {
                return new Promise(async (resolve) => {
                    await ch.assertQueue(settings.publish_queue.name, settings.publish_queue.options);
                    await ch.purgeQueue(settings.publish_queue.name);
                    resolve();
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

        await externalWorkerMock.consume(settings.publish_queue.name, {});
        assert.isTrue(await rpcWorker.isWorkerReady());
    });

    it("worker should send 1 msg to worker and receive multiple with proper headers #integration", async () => {
        const forwarded: JobMessage[] = [];
        const settings: IAmqpWorkerSettings = {
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
        const redis: any = {
            isProcessed() {return false},
            setProcessed() {return},
        };
        const partialForwarder: IPartialForwarder = {
            forwardPart: (jm: JobMessage) => {
                forwarded.push(jm);
                return Promise.resolve();
            },
        };
        const counterPublisher: ICounterPublisher = {
            send: async () => Promise.resolve(),
        };
        const assertionPublisher: IPublisher = {
            sendToQueue: ():  Promise<void> => { assert.fail("This should be never called."); },
            publish: ():  Promise<void> => { assert.fail("This should be never called."); },
        };
        const rpcWorker = new AmqpNonBlockingWorker(conn, settings, redis, partialForwarder, counterPublisher, assertionPublisher);
        const publisher = new Publisher(conn, (ch: Channel) =>  Promise.resolve() );
        const externalWorkerMock = new SimpleConsumer(
            conn,
            (ch: Channel) => {
                return new Promise(async (resolve) => {
                    await ch.assertQueue(settings.publish_queue.name, settings.publish_queue.options);
                    await ch.purgeQueue(settings.publish_queue.name);
                    resolve();
                });
            },
            async (msg: Message) => {
                // check if message has all expected headers
                assert.lengthOf(msg.properties.correlationId, 36); // uuidv4 length
                assert.equal(
                    msg.properties.replyTo,
                    `pipes.${settings.node_label.topology_id}.${settings.node_label.id}_reply`,
                );
                assert.isTrue(Headers.containsAllMandatory(msg.properties.headers));
                const hdrs = new Headers(msg.properties.headers);
                assert.equal(hdrs.getPFHeader(Headers.NODE_ID), "507f191e810c19729de860ea");
                assert.equal(hdrs.getPFHeader(Headers.NODE_NAME), "amqprpcnode");
                assert.equal(hdrs.getPFHeader(Headers.CORRELATION_ID), "amqp.worker.correlation_id");
                assert.equal(hdrs.getPFHeader(Headers.PROCESS_ID), "amqp.worker.process_id");
                assert.equal(hdrs.getPFHeader(Headers.PARENT_ID), "");
                assert.equal(hdrs.getPFHeader(Headers.SEQUENCE_ID), "1");
                assert.equal(hdrs.getPFHeader(Headers.TOPOLOGY_ID), "topoId");
                assert.equal(hdrs.getPFHeader(Headers.TOPOLOGY_NAME), "topoName");
                assert.equal(hdrs.getPFHeader("foo"), "bar");

                // Send partial messages and the confirmation message afterwards
                let j = 1;
                const proms = [];
                while (j <= 5) {
                    const replyHeaders = JSON.parse(JSON.stringify(msg.properties.headers));
                    replyHeaders["pf-sequence-id"] = j;
                    replyHeaders["pf-result-code"] = ResultCode.SUCCESS;
                    replyHeaders["pf-result-message"] = "ok";

                    const content = Buffer.from(`${j}`);

                    const p = publisher.sendToQueue(
                        msg.properties.replyTo,
                        content,
                        {
                            type: AmqpNonBlockingWorker.BATCH_ITEM_TYPE,
                            correlationId: msg.properties.correlationId,
                            headers: replyHeaders,
                        },
                    );
                    proms.push(p);
                    j++;
                }

                // Send confirmation message after all BATCH_ITEM messages are sent
                await Promise.all(proms);
                const finalHeaders = JSON.parse(JSON.stringify(msg.properties.headers));
                finalHeaders["pf-sequence-id"] = 1;
                finalHeaders["pf-result-code"] = ResultCode.SPLITTER_BATCH_END;
                finalHeaders["pf-result-message"] = "everything okay";

                publisher.sendToQueue(
                    msg.properties.replyTo,
                    Buffer.from(""),
                    {
                        type: AmqpNonBlockingWorker.BATCH_END_TYPE,
                        correlationId: msg.properties.correlationId,
                        headers: finalHeaders,
                    },
                );
            },
        );

        await externalWorkerMock.consume(settings.publish_queue.name, {});

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
            Buffer.from(JSON.stringify({ settings: {}, data: "test" })),
        );

        const outMsgs = await rpcWorker.processData(jobMsg);

        assert.lengthOf(outMsgs, 1);
        const outMsg: JobMessage = outMsgs[0];

        assert.instanceOf(outMsg, JobMessage);
        assert.equal(outMsg.getMultiplier(), 0);
        assert.isFalse(outMsg.getForwardSelf());
        assert.equal(ResultCode.SPLITTER_BATCH_END, outMsg.getResult().code);
        assert.equal("everything okay", outMsg.getResult().message);

        let i = 1;
        forwarded.forEach((splitMsg: JobMessage) => {
            assert.equal(i, splitMsg.getSequenceId());
            assert.equal(`${i}`, splitMsg.getContent());
            i++;
        });
    });
});