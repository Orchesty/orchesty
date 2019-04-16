import { assert } from "chai";
import "mocha";

import Headers from "../../../src/message/Headers";
import JobMessage from "../../../src/message/JobMessage";
import {ResultCode} from "../../../src/message/ResultCode";
import IPartialForwarder from "../../../src/node/drain/IPartialForwarder";
import JsonSplitterWorker, {IJsonSplitterWorkerSettings} from "../../../src/node/worker/JsonSplitterWorker";
import {INodeLabel} from "../../../src/topology/Configurator";

const settings: IJsonSplitterWorkerSettings = {
    node_label: {
        id: "someId",
        node_id: "507f191e810c19729de860ea",
        node_name: "splitter",
        topology_id: "topoId",
    },
};

describe("Splitter worker", () => {
    it("should fail when invalid JSON content format #unit", async () => {
        const partialForwarder: IPartialForwarder = {
            forwardPart: () => Promise.resolve(),
        };
        const node: INodeLabel = {id: "nodeId", node_id: "nodeId", node_name: "nodeName", topology_id: "topoId"};
        const headers = new Headers();
        headers.setPFHeader(Headers.CORRELATION_ID, "123");
        headers.setPFHeader(Headers.PROCESS_ID, "123");
        headers.setPFHeader(Headers.PARENT_ID, "");
        headers.setPFHeader(Headers.SEQUENCE_ID, "1");
        const msg = new JobMessage(node, headers.getRaw(), Buffer.from("{}{}{}"));
        const worker = new JsonSplitterWorker(settings, partialForwarder);

        const outMsgs = await worker.processData(msg);
        assert.lengthOf(outMsgs, 1);
        const outMsg: JobMessage = outMsgs[0];

        assert.equal(outMsg.getResult().code, ResultCode.INVALID_CONTENT);
        assert.include(outMsg.getResult().message, "Could not parse message content.");
    });

    it("should fail when JSON content is empty array #unit", async () => {
        const partialForwarder: IPartialForwarder = {
            forwardPart: () => Promise.resolve(),
        };
        const body = Buffer.from(JSON.stringify({ foo: "bar" }));
        const node: INodeLabel = {id: "nodeId", node_id: "nodeId", node_name: "nodeName", topology_id: "topoId"};
        const headers = new Headers();
        headers.setPFHeader(Headers.CORRELATION_ID, "123");
        headers.setPFHeader(Headers.PROCESS_ID, "123");
        headers.setPFHeader(Headers.PARENT_ID, "");
        headers.setPFHeader(Headers.SEQUENCE_ID, "1");
        const msg = new JobMessage(node, headers.getRaw(), body);
        const worker = new JsonSplitterWorker(settings, partialForwarder);

        const outMsgs = await worker.processData(msg);
        assert.lengthOf(outMsgs, 1);
        const outMsg: JobMessage = outMsgs[0];

        assert.equal(outMsg.getResult().code, ResultCode.INVALID_CONTENT);
        assert.include(outMsg.getResult().message, "Message content must be json array");
    });

    it("should split message #unit", async () => {
        const forwarded: JobMessage[] = [];
        const content = [
            { foo: "bar" },
            { foo: "baz" },
            { foo: "woo" },
        ];
        const node: INodeLabel = {id: "nodeId", node_id: "nodeId", node_name: "nodeName", topology_id: "topoId"};
        const headers = new Headers();
        headers.setPFHeader(Headers.CORRELATION_ID, "123");
        headers.setPFHeader(Headers.PROCESS_ID, "123");
        headers.setPFHeader(Headers.PARENT_ID, "");
        headers.setPFHeader(Headers.SEQUENCE_ID, "1");
        const msg = new JobMessage(node, headers.getRaw(), Buffer.from(JSON.stringify(content)));
        const partialForwarder: IPartialForwarder = {
            forwardPart: (forwardedMsg: JobMessage) => {
                forwarded.push(forwardedMsg);
                return Promise.resolve();
            },
        };
        const worker = new JsonSplitterWorker(settings, partialForwarder);

        const outMsgs = await worker.processData(msg);
        assert.lengthOf(outMsgs, 1);
        const outMsg: JobMessage = outMsgs[0];

        assert.equal(outMsg.getResult().code, ResultCode.SUCCESS);
        assert.equal(outMsg.getMultiplier(), 3);
        assert.isFalse(outMsg.getForwardSelf());
        // Split messages check
        let i: number = 0;
        forwarded.forEach((split) => {
            assert.equal(
                split.getContent(),
                JSON.stringify(content[i]),
            );
            i++;
        });
    });

});
