import { assert } from "chai";
import "mocha";

import {Channel, Message} from "amqplib";
import Connection from "lib-nodejs/dist/src/rabbitmq/Connection";
import Publisher from "lib-nodejs/dist/src/rabbitmq/Publisher";
import SimpleConsumer from "lib-nodejs/dist/src/rabbitmq/SimpleConsumer";
import {amqpConnectionOptions, mongoStorageOptions} from "../../src/config";
import MongoMessageStorage from "../../src/repeater/MongoMessageStorage";
import Repeater, {IRepeaterSettings} from "../../src/repeater/Repeater";

const conn = new Connection(amqpConnectionOptions);

describe.skip("Repeater", () => {
    it("should consume message and publish it after repeat_interval", (done) => {
        const settings: IRepeaterSettings = {
            input: { queue: { name: "repeater_a", options: {} } },
            check_timeout: 1000,
        };
        const outputQueue = "repeater_a_output";
        const msgContent = "content of repeated message";
        const msgProps = {
            replyTo: outputQueue,
            headers: {
                correlation_id: "somecorrid",
                process_id: "someprocid",
                repeat_interval: 500,
            },
        };

        const storage = new MongoMessageStorage(mongoStorageOptions);
        const repeater = new Repeater(settings, conn, storage);

        repeater.run();

        let sentTimestamp = Date.now();
        const publisher = new Publisher(conn, (ch: Channel) => {
            return new Promise((resolve) => {
                ch.assertQueue(settings.input.queue.name, settings.input.queue.options)
                    .then(() => {
                        sentTimestamp = Date.now();
                        return ch.purgeQueue(settings.input.queue.name);
                    })
                    .then(() => {
                        resolve();
                    });
            });
        });

        const consumer = new SimpleConsumer(
            conn,
            (ch: Channel) => {
                return new Promise((resolve) => {
                    ch.assertQueue(outputQueue)
                        .then(() => {
                            return ch.purgeQueue(outputQueue);
                        }).then(() => {
                            resolve();
                        });
                });
            },
            (msg: Message) => {
                // Here is the test resolution
                // Message consumed should be same as message produced but after timeout
                assert.equal(msg.content.toString(), msgContent);
                assert.deepEqual(msg.properties.headers, msgProps.headers);
                assert.isAtLeast(Date.now(),  sentTimestamp + msgProps.headers.repeat_interval);
                done();
            },
        );

        consumer.consume(outputQueue, {})
            .then(() => {
                return publisher.sendToQueue(settings.input.queue.name, new Buffer(msgContent), msgProps);
            });
    });

});
