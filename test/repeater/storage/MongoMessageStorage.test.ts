import { assert } from "chai";
import "mocha";

import {Db, MongoClient} from "mongodb";
import {mongoStorageOptions} from "../../../src/config";
import MongoMessageStorage from "../../../src/repeater/storage/MongoMessageStorage";

xdescribe("MongoMessageStorage", () => {
    let db: Db;
    const COLLECTION_NAME = "messages";

    before(async () => {
        const mongo = await MongoClient.connect(
            `mongodb://${mongoStorageOptions.host}/${mongoStorageOptions.db}`,
            {useNewUrlParser: true, useUnifiedTopology: true},
        );
        db = mongo.db();
    });

    it("saves save messages and get the subset of them that are expired #integration", async () => {
        const storage = new MongoMessageStorage(mongoStorageOptions);

        const msg1: any = {
            content: Buffer.from("some content expires immediately"),
            fields: {},
            properties: {
                headers: {
                    correlation_id: "corrid1",
                    process_id: "procid1",
                    repeat_interval: 0,
                },
            },
        };

        const msg2: any = {
            content: Buffer.from("some other content expires in 1s"),
            fields: {},
            properties: {
                headers: {
                    correlation_id: "corrid2",
                    process_id: "procid2",
                    repeat_interval: 1000,
                },
            },
        };

        await db.collection(COLLECTION_NAME).deleteMany({});
        await Promise.all([
            storage.save(msg1, msg1.properties.headers.repeat_interval),
            storage.save(msg2, msg2.properties.headers.repeat_interval),
        ]);

        // Only msg1 should be returned due to its repeat_interval
        const messages = await storage.findExpired();
        assert.lengthOf(messages, 1);
        const toRepeat = messages.pop();
        assert.equal(msg1.content.toString(), toRepeat.content.toString());
        assert.deepEqual(msg1.properties, toRepeat.properties);
        assert.deepEqual(msg1.fields, toRepeat.fields);

        // Checks if previously returned document is no longer in collection
        const documents = await db.collection(COLLECTION_NAME).find({}).toArray();
        assert.lengthOf(documents, 1);
        const remaining = documents.pop();
        assert.equal(msg2.content.toString(), remaining.content.toString());
        assert.deepEqual(msg2.properties, remaining.properties);
        assert.deepEqual(msg2.fields, remaining.fields);
    });

    it("should do keep collection as is if no document found via findExpired( #integration)", async () => {
        const storage = new MongoMessageStorage(mongoStorageOptions);
        const msg: any = {
            content: Buffer.from("expires in far future"),
            fields: {},
            properties: {
                headers: {
                    correlation_id: "corrid",
                    process_id: "procid",
                    repeat_interval: 50000,
                },
            },
        };

        await db.collection(COLLECTION_NAME).deleteMany({});
        await storage.save(msg, msg.properties.headers.repeat_interval);
        const documents = await db.collection(COLLECTION_NAME).find({}).toArray();

        assert.lengthOf(documents, 1);
        const remaining = documents.pop();
        assert.equal(msg.content.toString(), remaining.content.toString());
        assert.deepEqual(msg.properties, remaining.properties);
        assert.deepEqual(msg.fields, remaining.fields);

        const messages = await storage.findExpired();
        assert.lengthOf(messages, 0);
    });

});
