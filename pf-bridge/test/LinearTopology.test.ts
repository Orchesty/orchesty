import { assert } from "chai";
import "mocha";

import {Channel, Message} from "amqplib";
import * as bodyParser from "body-parser";
import * as express from "express";
import Connection from "lib-nodejs/dist/src/rabbitmq/Connection";
import Publisher from "lib-nodejs/dist/src/rabbitmq/Publisher";
import SimpleConsumer from "lib-nodejs/dist/src/rabbitmq/SimpleConsumer";
import * as config from "../src/config";
import {ResultCode} from "../src/message/ResultCode";
import {ITopologyConfigSkeleton} from "../src/topology/Configurator";
import {ICounterProcessInfo} from "../src/topology/counter/Counter";
import Pipes from "./../src/Pipes";

const testTopology: ITopologyConfigSkeleton = {
    id: "linear-topo",
    nodes: [
        {
            id: "first",
            resequencer: true,
            debug: {
                port: 4001,
                host: "localhost",
                url: "http://localhost:4001/status",
            },
            next: ["second"],
        },
        {
            id: "second",
            resequencer: true,
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
            resequencer: true,
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
        httpWorkerMock.use(bodyParser.text());
        httpWorkerMock.post("/httpworker1", (req, resp) => {
            assert.equal(req.body, msgTestContent);
            const respBody = req.body + " modified";
            resp.set({
                "pf-result-code": 0,
                "pf-result-message": "ok",
            });
            resp.status(200).send(JSON.stringify(respBody));
        });
        httpWorkerMock.listen(3050);

        const pip = new Pipes(testTopology);

        Promise.all([
            pip.startCounter(),
            pip.startNode(testTopology.nodes[0].id),
            pip.startNode(testTopology.nodes[1].id),
            pip.startNode(testTopology.nodes[2].id),
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
                                    pip.getTopologyConfig().counter.pub.exchange.name,
                                    pip.getTopologyConfig().counter.pub.routing_key,
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
                    assert.equal(data.total, pip.getTopologyConfig().nodes.length);
                    assert.equal(data.ok, pip.getTopologyConfig().nodes.length);
                    assert.equal(data.nok, 0);
                    const trace: string[] = [];
                    data.messages.forEach((info) => {
                        assert.equal(info.resultCode, ResultCode.SUCCESS);
                        trace.push(info.node);
                    });
                    assert.deepEqual(
                        trace,
                        [testTopology.nodes[0].id, testTopology.nodes[1].id, testTopology.nodes[2].id],
                    );
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
                        ch.assertQueue(firstQueue, {})
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
    });

});
