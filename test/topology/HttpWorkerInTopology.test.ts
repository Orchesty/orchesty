import { assert } from "chai";
import "mocha";

import {Channel, Message} from "amqplib";
import {Connection} from "amqplib-plus/dist/lib/Connection";
import {Publisher} from "amqplib-plus/dist/lib/Publisher";
import {SimpleConsumer} from "amqplib-plus/dist/lib/SimpleConsumer";
import * as bodyParser from "body-parser";
import * as express from "express";
import {TimeUtils} from "hb-utils/dist/lib/TimeUtils";
import {persistentQueues} from "../../src/config";
import * as config from "../../src/config";
import {ICounterProcessInfo} from "../../src/counter/CounterProcess";
import Headers from "../../src/message/Headers";
import {ResultCode} from "../../src/message/ResultCode";
import Pipes from "../../src/Pipes";
import Terminator from "../../src/terminator/Terminator";
import {ITopologyConfigSkeleton} from "../../src/topology/Configurator";

const testTopology: ITopologyConfigSkeleton = {
    id: "topo-with-http-worker-node",
    topology_id: "topo-with-http-worker-node",
    topology_name: "topo-with-http-worker-node",
    nodes: [
        {
            id: "http-worker-node",
            worker: {
                type: "worker.http",
                settings: {
                    host: "localhost",
                    method: "post",
                    port: 7600,
                    process_path: "/process",
                    status_path: "/status",
                    secure: false,
                    opts: {},
                },
            },
            debug: {
                port: 7601,
                host: "localhost",
                url: "http://localhost:7601/status",
            },
            next: ["capture-node"],
        },
        {
            id: "capture-node",
            worker: {
                type: "worker.capture",
                settings: {},
            },
            debug: {
                port: 7602,
                host: "localhost",
                url: "http://localhost:7602/status",
            },
            next: [],
        },
    ],
};

const amqpConn = new Connection(config.amqpConnectionOptions);
const firstQueue = `pipes.${testTopology.id}.${testTopology.nodes[0].id}`;

const httpMock = express();
httpMock.use(bodyParser.raw({
    type: () => true,
}));

httpMock.post("/process", (req, resp) => {
    const requestHeaders: any = req.headers;

    assert.equal(req.body.toString(), "original content");
    assert.isTrue(Headers.containsAllMandatory(requestHeaders));

    const replyHeaders = new Headers(requestHeaders);
    replyHeaders.setPFHeader(Headers.RESULT_CODE, `${ResultCode.SUCCESS}`);
    replyHeaders.setPFHeader(Headers.RESULT_MESSAGE, "ok");
    replyHeaders.setHeader(Headers.CONTENT_TYPE, "text/plain; charset=utf-8");

    resp.set(replyHeaders.getRaw());
    resp.status(200).send("changed content");
});
httpMock.listen(7600);

describe("Topology with HttpWorker Node", () => {
    it("Next node should receive changed content", async () => {
        const pip = new Pipes(testTopology);

        // manually set the terminator port not to collide with other tests
        const dic = pip.getDIContainer();
        dic.set("topology.terminator", () => new Terminator(8555, dic.get("counter.storage")));

        const [counter, httpNode, captureNode] = await Promise.all([
            pip.startCounter(),
            pip.startBridge("http-worker-node"),
            pip.startBridge("capture-node"),
        ]);

        let resultMessagesReceived: number = 0;
        const counterResultQueue = { name: `${testTopology.id}-results`, options: {} };
        const resultConsumer = new SimpleConsumer(
            amqpConn,
            async (ch: Channel) => {
                await ch.assertQueue(counterResultQueue.name, counterResultQueue.options);
                await ch.purgeQueue(counterResultQueue.name);
                await ch.bindQueue(
                    counterResultQueue.name,
                    pip.getTopologyConfig(false).counter.pub.exchange.name,
                    pip.getTopologyConfig(false).counter.pub.routing_key,
                );
            },
            (msg: Message) => {
                // Check if every received result messages is processed without error
                const data: ICounterProcessInfo = JSON.parse(msg.content.toString());
                assert.isTrue(data.success);
                assert.equal(data.total, 1);
                assert.equal(data.ok, 1);
                assert.equal(data.nok, 0);

                resultMessagesReceived++;
            },
        );

        await resultConsumer.consume(counterResultQueue.name, {});

        const publisher = new Publisher(
            amqpConn,
            async (ch: Channel) => {
                await ch.assertQueue(firstQueue, { durable: persistentQueues });
                await ch.purgeQueue(firstQueue);
            },
        );

        for (let i: number = 0; i < 10; i++) {
            const hdrs = new Headers();
            hdrs.setPFHeader(Headers.CORRELATION_ID, `some-correlation-id-${i}`);
            hdrs.setPFHeader(Headers.PROCESS_ID, `some-process-id-${i}`);
            hdrs.setPFHeader(Headers.PARENT_ID, "");
            hdrs.setPFHeader(Headers.SEQUENCE_ID, "0");
            hdrs.setPFHeader(Headers.TOPOLOGY_ID, testTopology.id);
            hdrs.setHeader(Headers.CONTENT_TYPE, "text/plain");

            const props = { headers: hdrs.getRaw(), timestamp: TimeUtils.nowMili() };

            publisher.sendToQueue(firstQueue, new Buffer("original content"), props);
        }

        const capturer: any = captureNode.getWorker();
        const messages = await capturer.getCaptured(1000);

        assert.lengthOf(messages, 10);
        assert.equal(resultMessagesReceived, 10);

        for (let j: number = 0; j < 10; j++) {
            const msg: {body: string, headers: any} = messages[j];
            assert.equal(msg.body, "changed content");
            assert.isTrue(Headers.containsAllMandatory(msg.headers));

            const h = new Headers(msg.headers);
            assert.include(h.getPFHeader(Headers.CORRELATION_ID), `some-correlation-id-`);
            assert.include(h.getPFHeader(Headers.PROCESS_ID), `some-process-id-`);
            assert.equal(h.getPFHeader(Headers.PARENT_ID), "");
            assert.equal(h.getPFHeader(Headers.SEQUENCE_ID), "0");
            assert.equal(h.getPFHeader(Headers.TOPOLOGY_ID), testTopology.id);
            assert.equal(h.getHeader(Headers.CONTENT_TYPE), "text/plain; charset=utf-8");
        }
    }).timeout(5000);

});
