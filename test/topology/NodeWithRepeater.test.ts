import { assert } from "chai";
import "mocha";

import {Channel, Message} from "amqplib";
import {Connection} from "amqplib-plus/dist/lib/Connection";
import {Publisher} from "amqplib-plus/dist/lib/Publisher";
import {SimpleConsumer} from "amqplib-plus/dist/lib/SimpleConsumer";
import * as bodyParser from "body-parser";
import * as express from "express";
import * as config from "../../src/config";
import {persistentQueues} from "../../src/config";
import {ICounterProcessInfo} from "../../src/counter/CounterProcess";
import Headers from "../../src/message/Headers";
import {ResultCode} from "../../src/message/ResultCode";
import Pipes from "../../src/Pipes";
import Terminator from "../../src/terminator/Terminator";
import {ITopologyConfigSkeleton} from "../../src/topology/Configurator";

const testTopology: ITopologyConfigSkeleton = {
    id: "topo-with-repeater",
    topology_id: "topo-with-repeater",
    topology_name: "topo-with-repeater",
    nodes: [
        {
            id: "start-node",
            // This worker will send REPEAT result code first request
            worker: {
                type: "worker.http",
                settings: {
                    host: "localhost",
                    method: "post",
                    port: 5050,
                    process_path: "/process-first",
                    status_path: "/status",
                    secure: false,
                    opts: {},
                },
            },
            debug: {
                port: 4101,
                host: "localhost",
                url: "http://localhost:4001/status",
            },
            next: ["end-node"],
        },
        {
            id: "end-node",
            worker: {
                type: "worker.http",
                settings: {
                    host: "localhost",
                    method: "post",
                    port: 5050,
                    process_path: "/process-second",
                    status_path: "/status",
                    secure: false,
                    opts: {},
                },
            },
            debug: {
                port: 4102,
                host: "localhost",
                url: "http://localhost:4002/status",
            },
            next: [],
        },
    ],
};

const amqpConn = new Connection(config.amqpConnectionOptions);
const firstQueue = `pipes.${testTopology.id}.${testTopology.nodes[0].id}`;

const httpWorkerMock = express();
httpWorkerMock.use(bodyParser.raw({
    type: () => true,
}));
let processedFirst = false;
httpWorkerMock.post("/process-first", (req, resp) => {
    if (!processedFirst) {
        processedFirst = true;
        const requestHeaders: any = req.headers;
        const replyHeaders = new Headers(requestHeaders);
        replyHeaders.setPFHeader(Headers.RESULT_CODE, `${ResultCode.REPEAT}`);
        replyHeaders.setPFHeader(Headers.RESULT_MESSAGE, "repeat me please");
        replyHeaders.setPFHeader(Headers.REPEAT_INTERVAL, "500");
        replyHeaders.setPFHeader(Headers.REPEAT_MAX_HOPS, "5");
        replyHeaders.setPFHeader(Headers.REPEAT_HOPS, "1");
        resp.set(replyHeaders.getRaw());
        resp.status(200).send(req.body);
    } else {
        const respBody = req.body + " modified";
        const requestHeaders: any = req.headers;
        const replyHeaders = new Headers(requestHeaders);
        replyHeaders.setPFHeader(Headers.RESULT_CODE, `${ResultCode.SUCCESS}`);
        replyHeaders.setPFHeader(Headers.RESULT_MESSAGE, "now ok");
        resp.set(replyHeaders.getRaw());
        resp.status(200).send(respBody);
    }
});
httpWorkerMock.post("/process-second", (req, resp) => {
    assert.equal(req.body.toString(), "test content modified");
    const requestHeaders: any = req.headers;
    const replyHeaders = new Headers(requestHeaders);
    replyHeaders.setPFHeader(Headers.RESULT_CODE, `${ResultCode.SUCCESS}`);
    replyHeaders.setPFHeader(Headers.RESULT_MESSAGE, "ok");

    const respBody = req.body + " again";
    resp.set(replyHeaders.getRaw());
    resp.status(200).send(respBody);
});
httpWorkerMock.listen(5050);

const testMsgContent = "test content";
const testMsgHeaders = new Headers();
testMsgHeaders.setPFHeader(Headers.CORRELATION_ID, "corrid");
testMsgHeaders.setPFHeader(Headers.PROCESS_ID, "procid");
testMsgHeaders.setPFHeader(Headers.PARENT_ID, "");
testMsgHeaders.setPFHeader(Headers.SEQUENCE_ID, "0");
testMsgHeaders.setPFHeader(Headers.TOPOLOGY_ID, testTopology.id);
testMsgHeaders.setHeader(Headers.CONTENT_TYPE, "text/plain");

describe("Node with repeater test", () => {
    it("in first node message should be repeated and then forwarded to second node", (done) => {
        const pip = new Pipes(testTopology);

        // manually set the terminator port not to collide with other tests
        const dic = pip.getDIContainer();
        dic.set("topology.terminator", () => new Terminator(8558, dic.get("counter.storage")));

        Promise.all([
            pip.startCounter(),
            pip.startRepeater(),
            pip.startBridge(testTopology.nodes[0].id),
            pip.startBridge(testTopology.nodes[1].id),
        ])
        .then(() => {
            // Prepares consumer of counter output
            // Prepares function for evaluation of test end
            const counterResultQueue = {
                name: "node-with-repeater-counter-result",
                options: { durable: persistentQueues },
            };
            const resultConsumer = new SimpleConsumer(
                amqpConn,
                (ch: Channel) => {
                    return new Promise((resolve) => {
                        ch.assertQueue(counterResultQueue.name, counterResultQueue.options)
                            .then(() => {
                                return ch.purgeQueue(counterResultQueue.name);
                            })
                            .then(() => {
                                return ch.bindQueue(
                                    counterResultQueue.name,
                                    pip.getTopologyConfig(false).counter.pub.exchange.name,
                                    pip.getTopologyConfig(false).counter.pub.routing_key,
                                );
                            })
                            .then(() => {
                                resolve();
                            });
                    });
                },
                (msg: Message) => {
                    // In this fn we evaluate expected incoming message and state if test is OK or failed
                    const data: ICounterProcessInfo = JSON.parse(msg.content.toString());
                    assert.equal(data.process_id, testMsgHeaders.getPFHeader(Headers.PROCESS_ID));
                    assert.equal(data.total, pip.getTopologyConfig(false).nodes.length);
                    assert.equal(data.ok, pip.getTopologyConfig(false).nodes.length);
                    assert.equal(data.nok, 0);
                    done();
                },
            );

            return resultConsumer.consume(counterResultQueue.name, {});
        })
        .then(() => {
            // Publish messages to the first queue
            const publisher = new Publisher(
                amqpConn,
                (ch: Channel) => {
                    return new Promise((resolve) => {
                        ch.assertQueue(firstQueue, { durable: persistentQueues })
                            .then(() => {
                                return ch.purgeQueue(firstQueue);
                            })
                            .then(() => {
                                resolve();
                            });
                    });
                },
            );
            return publisher.sendToQueue(firstQueue, new Buffer(testMsgContent), { headers: testMsgHeaders.getRaw() });
        });
    }).timeout(5000);

});
