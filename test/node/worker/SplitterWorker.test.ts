import { assert } from "chai";
import "mocha";

import JobMessage from "../../../src/message/JobMessage";
import {ResultCode} from "../../../src/message/ResultCode";
import SplitterWorker from "../../../src/node/worker/SplitterWorker";

describe("Splitter worker", () => {
    it("should fail when cannot JSON parse content", () => {
        const msg = new JobMessage("123", 1, {}, JSON.stringify("{foo : 1, }"));
        const worker = new SplitterWorker({node_id: "someId"});
        return worker.processData(msg)
            .then((outMsg: JobMessage) => {
                assert.equal(outMsg.getResult().status, ResultCode.INVALID_MESSAGE_CONTENT_FORMAT);
                assert.include(outMsg.getResult().message, "key is missing");
            });
    });

    it("should fail when JSON content is not array with some element", () => {
        const msg = new JobMessage("123", 1, {}, JSON.stringify({data: [], settings: {}}));
        const worker = new SplitterWorker({node_id: "someId"});
        return worker.processData(msg)
            .then((outMsg: JobMessage) => {
                assert.equal(outMsg.getResult().status, ResultCode.INVALID_MESSAGE_CONTENT_FORMAT);
                assert.include(outMsg.getResult().message, "is not array or is empty");
            });
    });

    it("should split message", () => {
        const content = {
            data: [
                { foo: "bar" },
                { foo: "baz" },
                { foo: "woo" },
            ],
            settings: {
                some: "thing",
            },
        };
        const msg = new JobMessage("123", 1, {}, JSON.stringify(content));
        const worker = new SplitterWorker({node_id: "someId"});
        return worker.processData(msg)
            .then((outMsg: JobMessage) => {
                assert.equal(outMsg.getResult().status, ResultCode.SUCCESS);
                assert.equal(outMsg.getSplit().length, 3);
                // Split messages check
                let i: number = 0;
                outMsg.getSplit().forEach((split) => {
                    assert.equal(
                        split.getContent(),
                        JSON.stringify({ data: content.data[i], settings: content.settings}),
                    );
                    i++;
                });
            });
    });

});
