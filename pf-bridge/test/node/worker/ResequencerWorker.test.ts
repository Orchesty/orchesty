import { assert } from "chai";
import "mocha";

import Headers from "../../../src/message/Headers";
import JobMessage from "../../../src/message/JobMessage";
import {ResultCode} from "../../../src/message/ResultCode";
import ResequencerWorker from "../../../src/node/worker/ResequencerWorker";
import {INodeLabel} from "../../../src/topology/Configurator";

describe("Resequencer worker", () => {
    it("should return same message when waiting for it #unit", async () => {
        const label: INodeLabel = {id: "nodeId", node_id: "nodeId", node_name: "nodeName", topology_id: "topoId"};
        const headers = new Headers();
        headers.setPFHeader(Headers.CORRELATION_ID, "123");
        headers.setPFHeader(Headers.PROCESS_ID, "123");
        headers.setPFHeader(Headers.PARENT_ID, "");
        headers.setPFHeader(Headers.SEQUENCE_ID, "0");
        const inMsg = new JobMessage(label, headers.getRaw(), Buffer.from("{}{}{}"));

        const worker = new ResequencerWorker({ node_label: label });

        let outMsgs = await worker.processData(inMsg);
        assert.lengthOf(outMsgs, 1);
        let outMsg: JobMessage = outMsgs[0];
        assert.equal(outMsg, inMsg);
        assert.equal(outMsg.getResult().code, ResultCode.SUCCESS);

        inMsg.getHeaders().setPFHeader(Headers.SEQUENCE_ID, "1");
        outMsgs = await worker.processData(inMsg);
        assert.lengthOf(outMsgs, 1);
        outMsg = outMsgs[0];
        assert.equal(outMsg, inMsg);
        assert.equal(outMsg.getResult().code, ResultCode.SUCCESS);
    });

    it("should return empty array when waiting for some previous message #unit", async () => {
        const label: INodeLabel = {id: "nodeId", node_id: "nodeId", node_name: "nodeName", topology_id: "topoId"};
        const headers = new Headers();
        headers.setPFHeader(Headers.CORRELATION_ID, "123");
        headers.setPFHeader(Headers.PROCESS_ID, "123");
        headers.setPFHeader(Headers.PARENT_ID, "");
        headers.setPFHeader(Headers.SEQUENCE_ID, "0");
        const inMsg = new JobMessage(label, headers.getRaw(), Buffer.from("{}{}{}"));

        const worker = new ResequencerWorker({ node_label: label });

        let outMsgs = await worker.processData(inMsg);
        assert.lengthOf(outMsgs, 1);
        const outMsg: JobMessage = outMsgs[0];
        assert.equal(outMsg, inMsg);
        assert.equal(outMsg.getResult().code, ResultCode.SUCCESS);

        inMsg.getHeaders().setPFHeader(Headers.SEQUENCE_ID, "5");
        outMsgs = await worker.processData(inMsg);
        assert.lengthOf(outMsgs, 0);
    });

    it("should return empty array when trying to process message with the same sequenceId #unit", async () => {
        const label: INodeLabel = {id: "nodeId", node_id: "nodeId", node_name: "nodeName", topology_id: "topoId"};
        const headers = new Headers();
        headers.setPFHeader(Headers.CORRELATION_ID, "123");
        headers.setPFHeader(Headers.PROCESS_ID, "123");
        headers.setPFHeader(Headers.PARENT_ID, "");
        headers.setPFHeader(Headers.SEQUENCE_ID, "0");
        const inMsg = new JobMessage(label, headers.getRaw(), Buffer.from("{}{}{}"));

        const worker = new ResequencerWorker({ node_label: label });

        let outMsgs = await worker.processData(inMsg);
        assert.lengthOf(outMsgs, 1);
        const outMsg: JobMessage = outMsgs[0];
        assert.equal(outMsg, inMsg);
        assert.equal(outMsg.getResult().code, ResultCode.SUCCESS);

        outMsgs = await worker.processData(inMsg);
        assert.lengthOf(outMsgs, 0);
    });

    it("should always return that his worker is ready #unit", async () => {
        const label: INodeLabel = {id: "nodeId", node_id: "nodeId", node_name: "nodeName", topology_id: "topoId"};
        const worker = new ResequencerWorker({ node_label: label });
        assert.isTrue(await worker.isWorkerReady());
    });

});
