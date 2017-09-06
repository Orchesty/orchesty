import { assert } from "chai";
import "mocha";

import {Channel} from "amqplib";
import * as bodyParser from "body-parser";
import * as express from "express";
import Connection from "lib-nodejs/dist/src/rabbitmq/Connection";
import Publisher from "lib-nodejs/dist/src/rabbitmq/Publisher";
import SimpleConsumer from "lib-nodejs/dist/src/rabbitmq/SimpleConsumer";
import * as config from "../src/config";
import Pipes from "./../src/Pipes";
import { testTopology } from "./topology";

const firstQueue = "pipes.test-topo.first";

describe("Topology overall test", () => {
    it("will start all nodes", (done) => {
        const testContent = { val: "test content" };

        const httpWorkerMock = express();
        httpWorkerMock.use(bodyParser.json());
        httpWorkerMock.post("/httpworker1", (req, resp) => {
            assert.deepEqual(req.body, testContent);
            const updated = req.body;
            updated.val = updated.val + " modified";
            resp.status(200).send(JSON.stringify(updated));
        });
        httpWorkerMock.listen(3000);

        const pip = new Pipes(testTopology, config.amqpConnectionOptions);

        Promise.all([
            pip.startCounter(),
            pip.startNode("first"),
            pip.startNode("second"),
            pip.startNode("third"),
        ])
        .then(() => {
            // Publish messages to the first queue
            const publisher = new Publisher(
                new Connection(config.amqpConnectionOptions),
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
            return publisher.sendToQueue(
                firstQueue,
                new Buffer(JSON.stringify(testContent)),
                { headers: { job_id: "test", sequence_id: 1 } },
            );
        });
    });

});
