import { assert } from "chai";
import "mocha";

import {Message} from "amqplib";
import {Db, MongoClient} from "mongodb";
import {mongoStorageOptions} from "../../src/config";
import MongoMessageStorage from "../../src/repeater/MongoMessageStorage";

const mongo = MongoClient.connect(`mongodb://${mongoStorageOptions.host}/${mongoStorageOptions.db}`);
const COLLECTION_NAME = "messages";

describe("MongoMessageStorage", () => {
    it("saves save messages and get the subset of them that are expired", () => {
        const storage = new MongoMessageStorage(mongoStorageOptions);

        const msg1: Message = {
            content: new Buffer("some content expires immediately"),
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
            content: new Buffer("some other content expires in 1s"),
            fields: {},
            properties: {
                headers: {
                    correlation_id: "corrid2",
                    process_id: "procid2",
                    repeat_interval: 1000,
                },
            },
        };

        let db: Db;
        return mongo
            .then((mongodb: Db) => {
                db = mongodb;
                return db.collection(COLLECTION_NAME).drop();
            })
            .then(() => {
                return Promise.all([
                    storage.save(msg1, msg1.properties.headers.repeat_interval),
                    storage.save(msg2, msg2.properties.headers.repeat_interval),
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
            })
            .then(() => {
                // Checks if previously returned document is no longer in collection
                return db.collection(COLLECTION_NAME).find({}).toArray();
            })
            .then((documents: any[]) => {
                assert.lengthOf(documents, 1);
                const remaining = documents.pop();
                assert.equal(msg2.content.toString(), remaining.content.toString());
                assert.deepEqual(msg2.properties, remaining.properties);
                assert.deepEqual(msg2.fields, remaining.fields);
            });
    });

    it("should do keep collection as is if no document found via findExpired()", () => {
        const storage = new MongoMessageStorage(mongoStorageOptions);
        const msg: Message = {
            content: new Buffer("expires in far future"),
            fields: {},
            properties: {
                headers: {
                    correlation_id: "corrid",
                    process_id: "procid",
                    repeat_interval: 50000,
                },
            },
        };

        let db: Db;
        return mongo
            .then((mongodb: Db) => {
                db = mongodb;
                return db.collection(COLLECTION_NAME).drop();
            })
            .then(() => {
                return storage.save(msg, msg.properties.headers.repeat_interval);
            })
            .then(() => {
                return db.collection(COLLECTION_NAME).find({}).toArray();
            })
            .then((documents: any[]) => {
                assert.lengthOf(documents, 1);
                const remaining = documents.pop();
                assert.equal(msg.content.toString(), remaining.content.toString());
                assert.deepEqual(msg.properties, remaining.properties);
                assert.deepEqual(msg.fields, remaining.fields);
            })
            .then(() => {
                return storage.findExpired();
            }).then((messages: Message[]) => {
                assert.lengthOf(messages, 0);
            });
    });

});
