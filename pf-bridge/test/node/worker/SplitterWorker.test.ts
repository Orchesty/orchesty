import { assert } from "chai";
import "mocha";

import JobMessage from "../../../src/message/JobMessage";
import {ResultCode} from "../../../src/message/ResultCode";
import IPartialForwarder from "../../../src/node/drain/IPartialForwarder";
import SplitterWorker, {ISplitterWorkerSettings} from "../../../src/node/worker/SplitterWorker";

const settings: ISplitterWorkerSettings = {
    node_id: "someId",
};

describe("Splitter worker", () => {
    it("should fail when invalid JSON content format", () => {
        const msg = new JobMessage("nid", "123", "123", "", 1, {}, new Buffer(JSON.stringify("{foo : 1, }")));
        const partialForwarder: IPartialForwarder = {
            forwardPart: () => Promise.resolve(),
        };
        const worker = new SplitterWorker(settings, partialForwarder);
        return worker.processData(msg)
            .then((outMsg: JobMessage) => {
                assert.equal(outMsg.getResult().code, ResultCode.INVALID_CONTENT);
                assert.include(outMsg.getResult().message, "key is missing");
            });
    });

    it("should fail when JSON content is not array with some element", () => {
        const body = new Buffer(JSON.stringify({data: [], settings: {}}));
        const msg = new JobMessage("nid", "123", "123", "", 1, {}, body);
        const partialForwarder: IPartialForwarder = {
            forwardPart: () => Promise.resolve(),
        };
        const worker = new SplitterWorker(settings, partialForwarder);
        return worker.processData(msg)
            .then((outMsg: JobMessage) => {
                assert.equal(outMsg.getResult().code, ResultCode.INVALID_CONTENT);
                assert.include(outMsg.getResult().message, "is not array or is empty");
            });
    });

    it("should split message", () => {
        const forwarded: JobMessage[] = [];
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
        const msg = new JobMessage("nid", "123", "123", "", 1, {}, new Buffer(JSON.stringify(content)));
        const partialForwarder: IPartialForwarder = {
            forwardPart: (forwardedMsg: JobMessage) => {
                forwarded.push(forwardedMsg);
                return Promise.resolve();
            },
        };
        const worker = new SplitterWorker(settings, partialForwarder);
        return worker.processData(msg)
            .then((outMsg: JobMessage) => {
                assert.equal(outMsg.getResult().code, ResultCode.SUCCESS);
                assert.equal(outMsg.getMultiplier(), 3);
                assert.isFalse(outMsg.getForwardSelf());
                // Split messages check
                let i: number = 0;
                forwarded.forEach((split) => {
                    assert.equal(
                        split.getContent(),
                        JSON.stringify({ data: content.data[i], settings: content.settings}),
                    );
                    i++;
                });
            });
    });

});
