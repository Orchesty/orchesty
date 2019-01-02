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
    id: "linear-topo",
    topology_id: "linear-topo",
    topology_name: "linear-topo",
    nodes: [
        {
            id: "first",
            debug: {
                port: 4001,
                host: "localhost",
                url: "http://localhost:4001/status",
            },
            next: ["second"],
        },
        {
            id: "second",
            worker: {
                type: "worker.http",
                settings: {
                    host: "localhost",
                    method: "post",
                    port: 3050,
                    process_path: "/httpworker1/",
                    status_path: "/status",
                    secure: false,
                    opts: {},
                },
            },
            debug: {
                port: 4002,
                host: "localhost",
                url: "http://localhost:4002/status",
            },
            next: ["third"],
        },
        {
            id: "third",
            worker: {
                type: "worker.uppercase",
                settings: {},
            },
            debug: {
                port: 4003,
                host: "localhost",
                url: "http://localhost:4003/status",
            },
            next: [],
        },
    ],
};

const amqpConn = new Connection(config.amqpConnectionOptions);
const firstQueue = `pipes.${testTopology.id}.${testTopology.nodes[0].id}`;

describe("Linear Topology test", () => {
    it("complete flow of messages till the end", (done) => {
        const msgTestContent = "test content";
        const msgHeaders = { headers: {
            "pf-correlation-id": "corrid",
            "pf-process-id": "test",
            "pf-parent-id": "",
            "pf-sequence-id": 0,
            "pf-topology-id": "topoid",
            "pf-topology-name": "toponame",
            "pf-foo": "bar",
            "foo": "bar",
            "content-type": "text/plain",
        }};

        const httpWorkerMock = express();
        httpWorkerMock.use(bodyParser.raw({
            type: () => true,
        }));
        httpWorkerMock.post("/httpworker1", (req, resp) => {
            assert.equal(req.body.toString(), msgTestContent);
            const respBody = req.body + " modified";

            const requestHeaders: any = req.headers;
            const replyHeaders = new Headers(requestHeaders);
            replyHeaders.setPFHeader(Headers.RESULT_CODE, `${ResultCode.SUCCESS}`);
            replyHeaders.setPFHeader(Headers.RESULT_MESSAGE, "ok");

            resp.set(replyHeaders.getRaw());
            resp.status(200).send(JSON.stringify(respBody));
        });
        httpWorkerMock.listen(3050);

        const pip = new Pipes(testTopology);

        // manually set the terminator port not to collide with other tests
        const dic = pip.getDIContainer();
        dic.set("topology.terminator", () => new Terminator(8556, dic.get("counter.storage")));

        Promise.all([
            pip.startCounter(),
            pip.startBridge(testTopology.nodes[0].id),
            pip.startBridge(testTopology.nodes[1].id),
            pip.startBridge(testTopology.nodes[2].id),
        ])
        .then(() => {
            // Prepares consumer of counter output
            // Prepares function for evaluation of test end
            const counterResultQueue = {
                name: "linear-topology-counter-result",
                options: {},
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
                    assert.equal(data.process_id, msgHeaders.headers["pf-process-id"]);
                    assert.equal(data.total, 1);
                    assert.equal(data.ok, 1);
                    assert.equal(data.nok, 0);
                    const trace: string[] = [];
                    data.messages.forEach((info) => {
                        assert.equal(info.resultCode, ResultCode.SUCCESS);
                        trace.push(info.node);
                    });
                    assert.deepEqual(trace, [testTopology.nodes[2].id]);
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
            return publisher.sendToQueue(firstQueue, new Buffer(msgTestContent), msgHeaders);
        });
    }).timeout(5000);

});
