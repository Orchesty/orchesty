import { assert } from "chai";
import "mocha";

import * as bodyParser from "body-parser";
import * as express from "express";
import * as http from "http";
import Headers from "../../../src/message/Headers";
import JobMessage from "../../../src/message/JobMessage";
import {ResultCode} from "../../../src/message/ResultCode";
import HttpWorker from "../../../src/node/worker/HttpWorker";
import {INodeLabel} from "../../../src/topology/Configurator";

const httpServer = express();
const bodyParserRaw = {
    type: () => true,
};

httpServer.use(bodyParser.raw(bodyParserRaw));
httpServer.post("/ok", (req, resp) => {
    assert.deepEqual(JSON.parse(req.body), { val: "original" });
    assert.equal(req.headers["pf-node-name"], "httpworker");
    assert.equal(req.headers["pf-node-id"], "507f191e810c19729de860ea");

    const requestHeaders: any = req.headers;
    const replyHeaders = new Headers(requestHeaders);
    replyHeaders.setPFHeader(Headers.RESULT_CODE, `${ResultCode.SUCCESS}`);
    replyHeaders.setPFHeader(Headers.RESULT_MESSAGE, "ok");

    // delay response a little
    setTimeout(() => {
        resp.set(replyHeaders.getRaw());
        resp.status(200).send(JSON.stringify({ val: "modified" }));
    }, 20);
});
httpServer.post("/invalid-status-code", (req, resp) => {
    assert.deepEqual(JSON.parse(req.body), { val: "original" });

    const requestHeaders: any = req.headers;
    const replyHeaders = new Headers(requestHeaders);
    replyHeaders.setPFHeader(Headers.RESULT_CODE, `${ResultCode.WORKER_TIMEOUT}`);
    replyHeaders.setPFHeader(Headers.RESULT_MESSAGE, "some error");

    resp.set(replyHeaders.getRaw());
    resp.status(500).send(JSON.stringify({ val: "modified but 500" }));
});
httpServer.post("/invalid-result-code", (req, resp) => {
    assert.deepEqual(JSON.parse(req.body), { val: "original" });

    const requestHeaders: any = req.headers;
    const replyHeaders = new Headers(requestHeaders);
    replyHeaders.setPFHeader(Headers.RESULT_CODE, "999");
    replyHeaders.setPFHeader(Headers.RESULT_MESSAGE, "ok");

    resp.set(replyHeaders.getRaw());
    resp.status(200).send(JSON.stringify({ val: "modified" }));
});
httpServer.post("/empty-result-body", (req, resp) => {
    assert.deepEqual(JSON.parse(req.body), { val: "original" });

    const requestHeaders: any = req.headers;
    const replyHeaders = new Headers(requestHeaders);
    replyHeaders.setPFHeader(Headers.RESULT_CODE, `${ResultCode.SUCCESS}`);
    replyHeaders.setPFHeader(Headers.RESULT_MESSAGE, "ok");

    resp.set(replyHeaders.getRaw());
    resp.status(200).send();
});
httpServer.post("/ok-xml", (req, resp) => {
    assert.deepEqual(JSON.parse(req.body), { val: "original" });

    const requestHeaders: any = req.headers;
    const replyHeaders = new Headers(requestHeaders);
    replyHeaders.setPFHeader(Headers.RESULT_CODE, `${ResultCode.SUCCESS}`);
    replyHeaders.setPFHeader(Headers.RESULT_MESSAGE, "ok");
    replyHeaders.setPFHeader(Headers.CONTENT_TYPE, "application/xml");

    resp.set(replyHeaders.getRaw());
    resp.status(200).send("<?xml version=\"1.0\" encoding=\"UTF-8\"?><root>some content</root>");
});
httpServer.listen(4020);

const metricsMock = {
    send: () => Promise.resolve("sent"),
    addTag: () => { return; },
    removeTag: () => { return; },
};

function createHttpWorker(port: number, processPath: string, host: string = "localhost") {
    return new HttpWorker({
        node_label: {
            id: "someId",
            node_id: "507f191e810c19729de860ea",
            node_name: "httpworker",
            topology_id: "topoId",
        },
        host,
        method: "post",
        port,
        process_path: processPath,
        status_path: "/status",
        secure: false,
        opts : {},
    }, metricsMock);
}

describe("HttpWorker", () => {
    it("should convert JobMessage to http request, receive response and set message result", () => {
        const node: INodeLabel = {id: "nodeId", node_id: "nodeId", node_name: "nodeName", topology_id: "topoId"};
        const headers = new Headers();
        headers.setPFHeader(Headers.CORRELATION_ID, "123");
        headers.setPFHeader(Headers.PROCESS_ID, "123");
        headers.setPFHeader(Headers.PARENT_ID, "");
        headers.setPFHeader(Headers.SEQUENCE_ID, "1");
        // headers.setHeader("content-type", "application/json");
        const msg = new JobMessage(node, headers.getRaw(), new Buffer(JSON.stringify({ val: "original" })));

        const worker = createHttpWorker(4020, "/ok");

        return worker.processData(msg)
            .then((outMsgs: JobMessage[]) => {
                assert.lengthOf(outMsgs, 1);
                const outMsg: JobMessage = outMsgs[0];

                assert.equal(outMsg.getResult().code, ResultCode.SUCCESS);
                assert.equal(outMsg.getContent(), JSON.stringify({ val: "modified" }));
            });
    });

    it("should return original message content when server responds with error", () => {
        const node: INodeLabel = {id: "nodeId", node_id: "nodeId", node_name: "nodeName", topology_id: "topoId"};
        const headers = new Headers();
        headers.setPFHeader(Headers.CORRELATION_ID, "123");
        headers.setPFHeader(Headers.PROCESS_ID, "123");
        headers.setPFHeader(Headers.PARENT_ID, "");
        headers.setPFHeader(Headers.SEQUENCE_ID, "1");
        const msg = new JobMessage(node, headers.getRaw(), new Buffer(JSON.stringify({ val: "original" })));

        const worker = createHttpWorker(4020, "/invalid-status-code");

        return worker.processData(msg)
            .then((outMsgs: JobMessage[]) => {
                assert.lengthOf(outMsgs, 1);
                const outMsg: JobMessage = outMsgs[0];

                assert.equal(outMsg.getResult().code, ResultCode.HTTP_ERROR);
                assert.equal(outMsg.getContent(), JSON.stringify({ val: "original" }));
            });
    });

    it("should return modified message but be marked as failed due to result_status error", () => {
        const node: INodeLabel = {id: "nodeId", node_id: "nodeId", node_name: "nodeName", topology_id: "topoId"};
        const headers = new Headers();
        headers.setPFHeader(Headers.CORRELATION_ID, "123");
        headers.setPFHeader(Headers.PROCESS_ID, "123");
        headers.setPFHeader(Headers.PARENT_ID, "");
        headers.setPFHeader(Headers.SEQUENCE_ID, "1");
        const msg = new JobMessage(node, headers.getRaw(), new Buffer(JSON.stringify({ val: "original" })));

        const worker = createHttpWorker(4020, "/invalid-result-code");

        return worker.processData(msg)
            .then((outMsgs: JobMessage[]) => {
                assert.lengthOf(outMsgs, 1);
                const outMsg: JobMessage = outMsgs[0];

                assert.equal(outMsg.getResult().code, ResultCode.MISSING_RESULT_CODE);
                assert.equal(outMsg.getContent(), JSON.stringify({ val: "original" }));
            });
    });

    it("should return original message content when process_path does not exist", () => {
        const node: INodeLabel = {id: "nodeId", node_id: "nodeId", node_name: "nodeName", topology_id: "topoId"};
        const headers = new Headers();
        headers.setPFHeader(Headers.CORRELATION_ID, "123");
        headers.setPFHeader(Headers.PROCESS_ID, "123");
        headers.setPFHeader(Headers.PARENT_ID, "");
        headers.setPFHeader(Headers.SEQUENCE_ID, "1");
        const msg = new JobMessage(node, headers.getRaw(), new Buffer(JSON.stringify({ val: "original" })));

        const worker = createHttpWorker(4020, "/non-existing");

        return worker.processData(msg)
            .then((outMsgs: JobMessage[]) => {
                assert.lengthOf(outMsgs, 1);
                const outMsg: JobMessage = outMsgs[0];

                assert.equal(outMsg.getResult().code, ResultCode.HTTP_ERROR);
                assert.equal(outMsg.getContent(), JSON.stringify({ val: "original" }));
            });
    });

    it("should return original message content and set repeat code when worker timeouted on valid route", () => {
        const node: INodeLabel = {id: "nodeId", node_id: "nodeId", node_name: "nodeName", topology_id: "topoId"};
        const headers = new Headers();
        headers.setPFHeader(Headers.CORRELATION_ID, "123");
        headers.setPFHeader(Headers.PROCESS_ID, "123");
        headers.setPFHeader(Headers.PARENT_ID, "");
        headers.setPFHeader(Headers.SEQUENCE_ID, "1");
        const msg = new JobMessage(node, headers.getRaw(), new Buffer(JSON.stringify({ val: "original" })));

        const worker = createHttpWorker(4020, "/ok");
        // This should emit ETIMEDOUT error "/ok" responds after 20ms
        worker.setTimeout(5);

        return worker.processData(msg)
            .then((outMsgs: JobMessage[]) => {
                assert.lengthOf(outMsgs, 1);
                const outMsg: JobMessage = outMsgs[0];

                assert.equal(outMsg.getResult().code, ResultCode.REPEAT);
                assert.equal(outMsg.getContent(), JSON.stringify({ val: "original" }));
                assert.isTrue(outMsg.getHeaders().hasPFHeader(Headers.REPEAT_MAX_HOPS));
                assert.isTrue(outMsg.getHeaders().hasPFHeader(Headers.REPEAT_HOPS));
                assert.isTrue(outMsg.getHeaders().hasPFHeader(Headers.REPEAT_INTERVAL));
            });
    });

    it("should return empty data when worker returns empty body", () => {
        const node: INodeLabel = {id: "nodeId", node_id: "nodeId", node_name: "nodeName", topology_id: "topoId"};
        const headers = new Headers();
        headers.setPFHeader(Headers.CORRELATION_ID, "123");
        headers.setPFHeader(Headers.PROCESS_ID, "123");
        headers.setPFHeader(Headers.PARENT_ID, "");
        headers.setPFHeader(Headers.SEQUENCE_ID, "1");
        const msg = new JobMessage(node, headers.getRaw(), new Buffer(JSON.stringify({ val: "original" })));

        const worker = createHttpWorker(4020, "/empty-result-body");
        // TODO - find out why this test fails when using agent with keepAlive: true
        worker.setAgent(new http.Agent({ keepAlive: false }));

        return worker.processData(msg)
            .then((outMsgs: JobMessage[]) => {
                assert.lengthOf(outMsgs, 1);
                const outMsg: JobMessage = outMsgs[0];

                assert.equal(outMsg.getResult().code, ResultCode.SUCCESS);
                assert.equal(outMsg.getContent(), "");
            });
    });

    it("should return that worker is ready when it is really ready", () => {
        const workerServer = express();
        workerServer.get("/status", (req, resp) => {
            resp.sendStatus(200);
        });
        workerServer.listen(4321);

        const worker = createHttpWorker(4321, "/some-path");

        return worker.isWorkerReady()
            .then((isReady: boolean) => {
                assert.isTrue(isReady);
            });
    });

    it("should return that worker is not ready when it says it is not", () => {
        const workerServer = express();
        workerServer.post("/status", (req, resp) => {
            resp.sendStatus(500);
        });
        workerServer.listen(4322);

        const worker = createHttpWorker(4322, "/some-path");

        return worker.isWorkerReady()
            .then((isReady: boolean) => {
                assert.isFalse(isReady);
            });
    });

    it("should send json and receive xml", () => {
        const node: INodeLabel = {id: "nodeId", node_id: "nodeId", node_name: "nodeName", topology_id: "topoId"};
        const headers = new Headers();
        headers.setPFHeader(Headers.CORRELATION_ID, "123");
        headers.setPFHeader(Headers.PROCESS_ID, "123");
        headers.setPFHeader(Headers.PARENT_ID, "");
        headers.setPFHeader(Headers.SEQUENCE_ID, "1");
        headers.setHeader("content-type", "application/json");
        const msg = new JobMessage(node, headers.getRaw(), new Buffer(JSON.stringify({ val: "original" })));

        const worker = createHttpWorker(4020, "/ok-xml");

        return worker.processData(msg)
            .then((outMsgs: JobMessage[]) => {
                assert.lengthOf(outMsgs, 1);
                const outMsg: JobMessage = outMsgs[0];

                assert.equal(outMsg.getResult().code, ResultCode.SUCCESS);
                assert.equal(
                    outMsg.getContent(),
                    "<?xml version=\"1.0\" encoding=\"UTF-8\"?><root>some content</root>",
                );
            });
    });

    it("should return failed result message when remote http host does not exist", () => {
        const node: INodeLabel = {id: "nodeId", node_id: "nodeId", node_name: "nodeName", topology_id: "topoId"};
        const headers = new Headers();
        headers.setPFHeader(Headers.CORRELATION_ID, "123");
        headers.setPFHeader(Headers.PROCESS_ID, "123");
        headers.setPFHeader(Headers.PARENT_ID, "");
        headers.setPFHeader(Headers.SEQUENCE_ID, "1");
        headers.setHeader("content-type", "application/json");
        const msg = new JobMessage(node, headers.getRaw(), new Buffer(JSON.stringify({ val: "original" })));

        const worker = createHttpWorker(4020, "/non-existing", "nonexistinghost");

        return worker.processData(msg)
            .then((outMsgs: JobMessage[]) => {
                assert.lengthOf(outMsgs, 1);
                const outMsg: JobMessage = outMsgs[0];

                assert.equal(outMsg.getResult().code, ResultCode.HTTP_ERROR);
                assert.equal(outMsg.getContent(), JSON.stringify({ val: "original" }));
            });
    });

});
