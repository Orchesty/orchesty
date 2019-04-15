import { assert } from "chai";
import "mocha";

import Headers from "../../../src/message/Headers";
import JobMessage from "../../../src/message/JobMessage";
import {ResultCode} from "../../../src/message/ResultCode";
import ResequencerWorker from "../../../src/node/worker/ResequencerWorker";
import {INodeLabel} from "../../../src/topology/Configurator";

describe("Resequencer worker", () => {
    it("should return same message when waiting for it", () => {
        const label: INodeLabel = {id: "nodeId", node_id: "nodeId", node_name: "nodeName", topology_id: "topoId"};
        const headers = new Headers();
        headers.setPFHeader(Headers.CORRELATION_ID, "123");
        headers.setPFHeader(Headers.PROCESS_ID, "123");
        headers.setPFHeader(Headers.PARENT_ID, "");
        headers.setPFHeader(Headers.SEQUENCE_ID, "0");
        const inMsg = new JobMessage(label, headers.getRaw(), Buffer.from("{}{}{}"));

        const worker = new ResequencerWorker({ node_label: label });

        return worker.processData(inMsg)
            .then((outMsgs: JobMessage[]) => {
                assert.lengthOf(outMsgs, 1);
                const outMsg: JobMessage = outMsgs[0];

                assert.equal(outMsg, inMsg);
                assert.equal(outMsg.getResult().code, ResultCode.SUCCESS);
            })
            .then(() => {
                inMsg.getHeaders().setPFHeader(Headers.SEQUENCE_ID, "1");

                return worker.processData(inMsg);
            })
            .then((outMsgs: JobMessage[]) => {
                assert.lengthOf(outMsgs, 1);
                const outMsg: JobMessage = outMsgs[0];

                assert.equal(outMsg, inMsg);
                assert.equal(outMsg.getResult().code, ResultCode.SUCCESS);
            });
    });

    it("should return empty array when waiting for some previous message", () => {
        const label: INodeLabel = {id: "nodeId", node_id: "nodeId", node_name: "nodeName", topology_id: "topoId"};
        const headers = new Headers();
        headers.setPFHeader(Headers.CORRELATION_ID, "123");
        headers.setPFHeader(Headers.PROCESS_ID, "123");
        headers.setPFHeader(Headers.PARENT_ID, "");
        headers.setPFHeader(Headers.SEQUENCE_ID, "0");
        const inMsg = new JobMessage(label, headers.getRaw(), Buffer.from("{}{}{}"));

        const worker = new ResequencerWorker({ node_label: label });

        return worker.processData(inMsg)
            .then((outMsgs: JobMessage[]) => {
                assert.lengthOf(outMsgs, 1);
                const outMsg: JobMessage = outMsgs[0];

                assert.equal(outMsg, inMsg);
                assert.equal(outMsg.getResult().code, ResultCode.SUCCESS);
            })
            .then(() => {
                inMsg.getHeaders().setPFHeader(Headers.SEQUENCE_ID, "5");

                return worker.processData(inMsg);
            })
            .then((outMsgs: JobMessage[]) => {
                assert.lengthOf(outMsgs, 0);
            });
    });

    it("should return empty array when trying to process message with the same sequenceId", () => {
        const label: INodeLabel = {id: "nodeId", node_id: "nodeId", node_name: "nodeName", topology_id: "topoId"};
        const headers = new Headers();
        headers.setPFHeader(Headers.CORRELATION_ID, "123");
        headers.setPFHeader(Headers.PROCESS_ID, "123");
        headers.setPFHeader(Headers.PARENT_ID, "");
        headers.setPFHeader(Headers.SEQUENCE_ID, "0");
        const inMsg = new JobMessage(label, headers.getRaw(), Buffer.from("{}{}{}"));

        const worker = new ResequencerWorker({ node_label: label });

        return worker.processData(inMsg)
            .then((outMsgs: JobMessage[]) => {
                assert.lengthOf(outMsgs, 1);
                const outMsg: JobMessage = outMsgs[0];

                assert.equal(outMsg, inMsg);
                assert.equal(outMsg.getResult().code, ResultCode.SUCCESS);
            })
            .then(() => {
                return worker.processData(inMsg);
            })
            .then((outMsgs: JobMessage[]) => {
                assert.lengthOf(outMsgs, 0);
            });
    });

    it("should always return that his worker is ready", () => {
        const label: INodeLabel = {id: "nodeId", node_id: "nodeId", node_name: "nodeName", topology_id: "topoId"};
        const worker = new ResequencerWorker({ node_label: label });
        return worker.isWorkerReady()
            .then((isReady: boolean) => {
                assert.isTrue(isReady);
            });
    });

});
