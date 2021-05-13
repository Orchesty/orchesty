import { assert } from "chai";
import "mocha";

import {Channel, Message} from "amqplib";
import {Connection, Publisher} from "amqplib-plus";
import {amqpConnectionOptions, mongoStorageOptions, persistentQueues} from "../../src/config";
import {SimpleConsumer} from "../../src/consumer/SimpleConsumer";
import Headers from "../../src/message/Headers";
import Repeater, {IRepeaterSettings} from "../../src/repeater/Repeater";
import MongoMessageStorage from "../../src/repeater/storage/MongoMessageStorage";

const conn = new Connection(amqpConnectionOptions);

describe("Repeater", () => {
    it("should consume message and publish it after repeat interval #integration", (done) => {
        const settings: IRepeaterSettings = {
            input: { queue: { name: "repeater_a", prefetch: 50, options: { durable: persistentQueues } } },
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
            return new Promise(async (resolve) => {
                await ch.assertQueue(settings.input.queue.name, settings.input.queue.options);
                await ch.purgeQueue(settings.input.queue.name);
                sentTimestamp = Date.now();
                resolve();
            });
        });

        const consumer = new SimpleConsumer(
            conn,
            (ch: Channel) => {
                return new Promise(async (resolve) => {
                    await ch.assertQueue(outputQueue, { durable: persistentQueues });
                    await ch.purgeQueue(outputQueue);
                    resolve();
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
                    Buffer.from(msgContent),
                    {headers: headersToSend.getRaw()},
                );
            });
    });

});
