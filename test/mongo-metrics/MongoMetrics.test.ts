import {MongoMetrics} from "../../src/mongo-metrics/MongoMetrics";

import {assert} from "chai";

describe("Metrics", () => {

    it("should create in defined format", async () => {
        const tags = {name: "appname", host: "10.28.30.45"};
        const m = new MongoMetrics("measurementName", tags, "mongo");

        const lineWithTime = await m.send({value1: 10000, value2: "some value"});
        const lineWithoutTime = await m.send({value1: 10000, value2: "some value2"}, false);

        const withoutTime = JSON.parse(lineWithoutTime).fields;
        assert.equal(10000, withoutTime.value1);
        assert.equal("some value2", withoutTime.value2);
        assert.isNotNull(withoutTime._id);
        assert.isUndefined(withoutTime.created);

        // Check if doc contains timestamp if generated with it
        const withTime = JSON.parse(lineWithTime).fields;
        assert.equal(10000, withTime.value1);
        assert.equal("some value", withTime.value2);
        assert.isNotNull(withTime._id);
        assert.isNotNull(withTime.created);
    });

    it("should create in defined format with lately added and removed tag", async () => {
        const tags = {name : "appname", host: "10.28.30.45"};
        const m = new MongoMetrics("measurementName", tags, "mongo");

        m.addTag("added", "foo");
        m.removeTag("invalid");
        m.removeTag("host");

        const line = await m.send({ value1: 10000, value2: "some value" }, false);

        const res = JSON.parse(line);
        assert.equal(10000, res.fields.value1);
        assert.equal("some value", res.fields.value2);
        assert.isNotNull(res.fields._id);
        assert.isUndefined(res.fields.created);
        assert.equal("appname", res.tags.name);
        assert.equal("foo", res.tags.added);
        assert.equal(2, Object.keys(res.tags).length);
    });

});
