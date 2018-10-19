import { assert } from "chai";
import "mocha";

import {Channel, Message} from "amqplib";
import {Connection} from "amqplib-plus/dist/lib/Connection";
import {Publisher} from "amqplib-plus/dist/lib/Publisher";
import {SimpleConsumer} from "amqplib-plus/dist/lib/SimpleConsumer";
import {amqpConnectionOptions, mongoStorageOptions, persistentQueues} from "../../src/config";
import Headers from "../../src/message/Headers";
import Repeater, {IRepeaterSettings} from "../../src/repeater/Repeater";
import MongoMessageStorage from "../../src/repeater/storage/MongoMessageStorage";

const conn = new Connection(amqpConnectionOptions);

describe("Repeater", () => {
    it("should consume message and publish it after repeat interval", (done) => {
        const settings: IRepeaterSettings = {
            input: { queue: { name: "repeater_a", options: { durable: persistentQueues } } },
            check_timeout: 1000,
        };
        const outputQueue = "repeater_a_output";
        const msgContent = "content of repeated message";
        const headersToSend = new Headers();
        headersToSend.setPFHeader(Headers.CORRELATION_ID, "somecorrid");
        headersToSend.setPFHeader(Headers.PROCESS_ID, "someprocid");
        headersToSend.setPFHeader(Headers.REPEAT_INTERVAL, "500");
        headersToSend.setPFHeader(Headers.REPEAT_QUEUE, outputQueue);
        headersToSend.setPFHeader(Headers.REPEAT_HOPS, "0");
        headersToSend.setPFHeader(Headers.REPEAT_MAX_HOPS, "5");

        const storage = new MongoMessageStorage(mongoStorageOptions);
        const repeater = new Repeater(settings, conn, storage);

        repeater.start();

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
                    ch.assertQueue(outputQueue, { durable: persistentQueues })
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
                assert.deepEqual(msg.properties.headers, headersToSend.getRaw());
                assert.isAtLeast(
                    Date.now(),
                    sentTimestamp + parseInt(headersToSend.getPFHeader(Headers.REPEAT_INTERVAL), 10),
                );
                done();
            },
        );

        consumer.consume(outputQueue, {})
            .then(() => {
                return publisher.sendToQueue(
                    settings.input.queue.name,
                    new Buffer(msgContent),
                    {headers: headersToSend.getRaw()},
                );
            });
    });

});
