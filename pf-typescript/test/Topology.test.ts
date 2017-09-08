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
import {ICounterJobInfo} from "../src/topology/counter/Counter";
import Pipes from "./../src/Pipes";
import { testTopology } from "./topology";

const amqpConn = new Connection(config.amqpConnectionOptions);
const firstQueue = "pipes.test-topo.first";

describe("Topology overall test", () => {
    it("will start all nodes", (done) => {
        const msgTestContent = { val: "test content" };
        const msgHeaders = { headers: { job_id: "test", sequence_id: 1 } };

        const httpWorkerMock = express();
        httpWorkerMock.use(bodyParser.json());
        httpWorkerMock.post("/httpworker1", (req, resp) => {
            assert.deepEqual(req.body, msgTestContent);
            const updated = req.body;
            updated.val = updated.val + " modified";
            resp.status(200).send(JSON.stringify(updated));
        });
        httpWorkerMock.listen(3000);

        const pip = new Pipes(testTopology);

        Promise.all([
            pip.startCounter(),
            pip.startNode("first"),
            pip.startNode("second"),
            pip.startNode("third"),
        ])
        .then(() => {
            // Prepare consumer of counter output
            // Prepares function for evaluation of test end
            const counterResultQueue = {
                name: "counter-result",
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
                    const data: ICounterJobInfo = JSON.parse(msg.content.toString());
                    assert.equal(data.id, msgHeaders.headers.job_id);
                    assert.equal(data.total, pip.getTopologyConfig().nodes.length);
                    assert.equal(data.ok, pip.getTopologyConfig().nodes.length);
                    assert.equal(data.nok, 0);
                    const trace: string[] = [];
                    data.messages.forEach((info) => {
                        assert.equal(info.resultCode, ResultCode.SUCCESS);
                        trace.push(info.node);
                    });
                    assert.deepEqual(trace, ["first", "second", "third"]);
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
            return publisher.sendToQueue(firstQueue, new Buffer(JSON.stringify(msgTestContent)), msgHeaders);
        });
    });

});
