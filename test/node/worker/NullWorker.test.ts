import { assert } from "chai";
import "mocha";

import Headers from "../../../src/message/Headers";
import JobMessage from "../../../src/message/JobMessage";
import {ResultCode} from "../../../src/message/ResultCode";
import NullWorker from "../../../src/node/worker/NullWorker";
import {INodeLabel} from "../../../src/topology/Configurator";

describe("Null worker", () => {
    it("should set result to success", () => {
        const node: INodeLabel = {id: "nodeId", node_id: "nodeId", node_name: "nodeName", topology_id: "topoId"};
        const headers = new Headers();
        headers.setPFHeader(Headers.CORRELATION_ID, "123");
        headers.setPFHeader(Headers.PROCESS_ID, "123");
        headers.setPFHeader(Headers.PARENT_ID, "");
        headers.setPFHeader(Headers.SEQUENCE_ID, "1");
        const inMsg = new JobMessage(node, headers.getRaw(), new Buffer("{}{}{}"));

        const worker = new NullWorker();
        return worker.processData(inMsg)
            .then((outMsgs: JobMessage[]) => {
                assert.lengthOf(outMsgs, 1);
                const outMsg: JobMessage = outMsgs[0];

                assert.equal(outMsg, inMsg);
                assert.equal(outMsg.getResult().code, ResultCode.SUCCESS);
            });
    });

    it("should always return that his worker is ready", () => {
        const worker = new NullWorker();
        return worker.isWorkerReady()
            .then((isReady: boolean) => {
                assert.isTrue(isReady);
            });
    });

});
