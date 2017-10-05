import { assert } from "chai";
import "mocha";

import {Message} from "amqplib";
import {mongoStorageOptions} from "../../src/config";
import MongoMessageStorage from "../../src/repeater/MongoMessageStorage";

describe("MongoMessageStorage", () => {
    it("saves save messages and get the subset of them that are expired", () => {

        const storage = new MongoMessageStorage(mongoStorageOptions);

        const msg1: Message = {
            content: new Buffer("some content"),
            fields: {},
            properties: {
                headers: {
                    correlation_id: "corrid1",
                    process_id: "procid1",
                    repeat_interval: 0,
                },
            },
        };

        const msg2: Message = {
            content: new Buffer("some other content"),
            fields: {},
            properties: {
                headers: {
                    correlation_id: "corrid2",
                    process_id: "procid2",
                    repeat_interval: 1000,
                },
            },
        };

        return storage.drop()
        .then(() => {
            return Promise.all([
                storage.save(msg1),
                storage.save(msg2),
            ]);
        })
        .then(() => {
            // Only msg1 should be returned due to its repeat_interval
            return storage.findExpired();
        }).then((messages: Message[]) => {
            assert.lengthOf(messages, 1);
            const toRepeat = messages.pop();
            assert.equal(msg1.content.toString(), toRepeat.content.toString());
            assert.deepEqual(msg1.properties, toRepeat.properties);
            assert.deepEqual(msg1.fields, toRepeat.fields);
        });
    });

});
