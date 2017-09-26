import { assert } from "chai";
import "mocha";

import JobMessage from "../../../src/message/JobMessage";
import {ResultCode} from "../../../src/message/ResultCode";
import SplitterWorker, {ISplitterWorkerSettings} from "../../../src/node/worker/SplitterWorker";
import IPartialForwarder from "../../../src/node/drain/IPartialForwarder";

const settings: ISplitterWorkerSettings = {
    node_id: "someId",
    partial_forwarder: {
        node_id: "someId",
        counter_event: {
            queue: {
                name: `pf..counter`,
                options: {},
            },
        },
        followers: [],
        resequencer: false,
    },
};

describe("Splitter worker", () => {
    it("should fail when cannot JSON parse content", () => {
        const msg = new JobMessage("123", "123", 1, {}, JSON.stringify("{foo : 1, }"));
        const partialForwarder: IPartialForwarder = {
            forwardPart: () => Promise.resolve(),
        };
        const worker = new SplitterWorker(settings, partialForwarder);
        return worker.processData(msg)
            .then((outMsg: JobMessage) => {
                assert.equal(outMsg.getResult().status, ResultCode.INVALID_MESSAGE_CONTENT_FORMAT);
                assert.include(outMsg.getResult().message, "key is missing");
            });
    });

    it("should fail when JSON content is not array with some element", () => {
        const msg = new JobMessage("123", "123", 1, {}, JSON.stringify({data: [], settings: {}}));
        const partialForwarder: IPartialForwarder = {
            forwardPart: () => Promise.resolve(),
        };
        const worker = new SplitterWorker(settings, partialForwarder);
        return worker.processData(msg)
            .then((outMsg: JobMessage) => {
                assert.equal(outMsg.getResult().status, ResultCode.INVALID_MESSAGE_CONTENT_FORMAT);
                assert.include(outMsg.getResult().message, "is not array or is empty");
            });
    });

    it("should split message", () => {
        let forwarded: JobMessage[] = [];
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
        const msg = new JobMessage("123", "123", 1, {}, JSON.stringify(content));
        const partialForwarder: IPartialForwarder = {
            forwardPart: (msg: JobMessage) => {
                forwarded.push(msg);
                return Promise.resolve();
            },
        };
        const worker = new SplitterWorker(settings, partialForwarder);
        return worker.processData(msg)
            .then((outMsg: JobMessage) => {
                assert.equal(outMsg.getResult().status, ResultCode.SUCCESS);
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
