import { assert } from "chai";
import "mocha";

import * as bodyParser from "body-parser";
import * as express from "express";
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
    resp.set({
        "pf-result-code": 0,
        "pf-result-message": "ok",
    });
    resp.status(200).send(JSON.stringify({ val: "modified" }));
});
httpServer.post("/invalid-status-code", (req, resp) => {
    assert.deepEqual(JSON.parse(req.body), { val: "original" });
    resp.set({
        "pf-result-code": 4001,
        "pf-result-message": "some error",
    });
    resp.status(500).send(JSON.stringify({ val: "modified but 500" }));
});
httpServer.post("/invalid-result-code", (req, resp) => {
    assert.deepEqual(JSON.parse(req.body), { val: "original" });
    resp.set({
        "pf-result-code": ResultCode.WORKER_TIMEOUT,
        "pf-result-message": "some error",
    });
    resp.status(200).send(JSON.stringify({ val: "modified" }));
});
httpServer.post("/empty-result-body", (req, resp) => {
    assert.deepEqual(JSON.parse(req.body), { val: "original" });
    resp.set({
        "pf-result-code": ResultCode.SUCCESS,
        "pf-result-message": "some error",
    });
    resp.status(200).send();
});
httpServer.post("/ok-xml", (req, resp) => {
    assert.deepEqual(JSON.parse(req.body), { val: "original" });
    resp.set({
        "pf-result-code": 0,
        "pf-result-message": "ok",
    });
    resp.set("content-type", "application/xml");
    resp.status(200).send("<?xml version=\"1.0\" encoding=\"UTF-8\"?><root>some content</root>");
});
httpServer.listen(4020);

describe("HttpWorker", () => {
    it("should convert JobMessage to http request, receive response and set message result", () => {
        const node: INodeLabel = {id: "nodeId", node_id: "nodeId", node_name: "nodeName"};
        const headers = new Headers();
        headers.setPFHeader(Headers.CORRELATION_ID, "123");
        headers.setPFHeader(Headers.PROCESS_ID, "123");
        headers.setPFHeader(Headers.PARENT_ID, "");
        headers.setPFHeader(Headers.SEQUENCE_ID, "1");
        // headers.setHeader("content-type", "application/json");
        const msg = new JobMessage(node, headers.getRaw(), new Buffer(JSON.stringify({ val: "original" })));
        const worker = new HttpWorker({
            node_label: { id: "someId", node_id: "507f191e810c19729de860ea", node_name: "httpworker" },
            host: "localhost",
            method: "post",
            port: 4020,
            process_path: "/ok",
            status_path: "/status",
            secure: false,
            opts : {},
        });

        return worker.processData(msg)
            .then((outMsg: JobMessage) => {
                assert.equal(outMsg.getResult().code, ResultCode.SUCCESS);
                assert.equal(outMsg.getContent(), JSON.stringify({ val: "modified" }));
            });
    });

    it("should return original message content when server responds with error", () => {
        const node: INodeLabel = {id: "nodeId", node_id: "nodeId", node_name: "nodeName"};
        const headers = new Headers();
        headers.setPFHeader(Headers.CORRELATION_ID, "123");
        headers.setPFHeader(Headers.PROCESS_ID, "123");
        headers.setPFHeader(Headers.PARENT_ID, "");
        headers.setPFHeader(Headers.SEQUENCE_ID, "1");
        const msg = new JobMessage(node, headers.getRaw(), new Buffer(JSON.stringify({ val: "original" })));
        const worker = new HttpWorker({
            node_label: { id: "someId", node_id: "507f191e810c19729de860ea", node_name: "httpworker" },
            host: "localhost",
            method: "post",
            port: 4020,
            process_path: "/invalid-status-code",
            status_path: "/status",
            secure: false,
            opts : {},
        });

        return worker.processData(msg)
            .then((outMsg: JobMessage) => {
                assert.equal(outMsg.getResult().code, ResultCode.HTTP_ERROR);
                assert.equal(outMsg.getContent(), JSON.stringify({ val: "original" }));
            });
    });

    it("should return modified message but be marged as failed due to result_status error", () => {
        const node: INodeLabel = {id: "nodeId", node_id: "nodeId", node_name: "nodeName"};
        const headers = new Headers();
        headers.setPFHeader(Headers.CORRELATION_ID, "123");
        headers.setPFHeader(Headers.PROCESS_ID, "123");
        headers.setPFHeader(Headers.PARENT_ID, "");
        headers.setPFHeader(Headers.SEQUENCE_ID, "1");
        const msg = new JobMessage(node, headers.getRaw(), new Buffer(JSON.stringify({ val: "original" })));
        const worker = new HttpWorker({
            node_label: { id: "someId", node_id: "507f191e810c19729de860ea", node_name: "httpworker" },
            host: "localhost",
            method: "post",
            port: 4020,
            process_path: "/invalid-result-code",
            status_path: "/status",
            secure: false,
            opts : {},
        });

        return worker.processData(msg)
            .then((outMsg: JobMessage) => {
                assert.equal(outMsg.getResult().code, ResultCode.WORKER_TIMEOUT);
                assert.equal(outMsg.getContent(), JSON.stringify({ val: "modified" }));
            });
    });

    it("should return original message content when process_path does not exist", () => {
        const node: INodeLabel = {id: "nodeId", node_id: "nodeId", node_name: "nodeName"};
        const headers = new Headers();
        headers.setPFHeader(Headers.CORRELATION_ID, "123");
        headers.setPFHeader(Headers.PROCESS_ID, "123");
        headers.setPFHeader(Headers.PARENT_ID, "");
        headers.setPFHeader(Headers.SEQUENCE_ID, "1");
        const msg = new JobMessage(node, headers.getRaw(), new Buffer(JSON.stringify({ val: "original" })));
        const worker = new HttpWorker({
            node_label: { id: "someId", node_id: "507f191e810c19729de860ea", node_name: "httpworker" },
            host: "localhost",
            method: "post",
            port: 4020,
            process_path: "/non-existing",
            status_path: "/status",
            secure: false,
            opts : {},
        });

        return worker.processData(msg)
            .then((outMsg: JobMessage) => {
                assert.equal(outMsg.getResult().code, ResultCode.HTTP_ERROR);
                assert.equal(outMsg.getContent(), JSON.stringify({ val: "original" }));
            });
    });

    it("should return empty data when worker returns empty body", () => {
        const node: INodeLabel = {id: "nodeId", node_id: "nodeId", node_name: "nodeName"};
        const headers = new Headers();
        headers.setPFHeader(Headers.CORRELATION_ID, "123");
        headers.setPFHeader(Headers.PROCESS_ID, "123");
        headers.setPFHeader(Headers.PARENT_ID, "");
        headers.setPFHeader(Headers.SEQUENCE_ID, "1");
        const msg = new JobMessage(node, headers.getRaw(), new Buffer(JSON.stringify({ val: "original" })));
        const worker = new HttpWorker({
            node_label: { id: "someId", node_id: "507f191e810c19729de860ea", node_name: "httpworker" },
            host: "localhost",
            method: "post",
            port: 4020,
            process_path: "/empty-result-body",
            status_path: "/status",
            secure: false,
            opts : {},
        });

        return worker.processData(msg)
            .then((outMsg: JobMessage) => {
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

        const worker = new HttpWorker({
            node_label: { id: "someId", node_id: "507f191e810c19729de860ea", node_name: "httpworker" },
            host: "localhost",
            method: "post",
            port: 4321,
            process_path: "/some-path",
            status_path: "/status",
            secure: false,
            opts : {},
        });

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

        const worker = new HttpWorker({
            node_label: { id: "someId", node_id: "507f191e810c19729de860ea", node_name: "httpworker" },
            host: "localhost",
            method: "post",
            port: 4322,
            process_path: "/some-path",
            status_path: "/status",
            secure: false,
            opts : {},
        });

        return worker.isWorkerReady()
            .then((isReady: boolean) => {
                assert.isFalse(isReady);
            });
    });

    it("should send json and receive xml", () => {
        const node: INodeLabel = {id: "nodeId", node_id: "nodeId", node_name: "nodeName"};
        const headers = new Headers();
        headers.setPFHeader(Headers.CORRELATION_ID, "123");
        headers.setPFHeader(Headers.PROCESS_ID, "123");
        headers.setPFHeader(Headers.PARENT_ID, "");
        headers.setPFHeader(Headers.SEQUENCE_ID, "1");
        headers.setHeader("content-type", "application/json");
        const msg = new JobMessage(node, headers.getRaw(), new Buffer(JSON.stringify({ val: "original" })));
        const worker = new HttpWorker({
            node_label: { id: "someId", node_id: "507f191e810c19729de860ea", node_name: "httpworker" },
            host: "localhost",
            method: "post",
            port: 4020,
            process_path: "/ok-xml",
            status_path: "/status",
            secure: false,
            opts : {},
        });

        return worker.processData(msg)
            .then((outMsg: JobMessage) => {
                assert.equal(outMsg.getResult().code, ResultCode.SUCCESS);
                assert.equal(
                    outMsg.getContent(),
                    "<?xml version=\"1.0\" encoding=\"UTF-8\"?><root>some content</root>",
                );
            });
    });

    it("should return failed result message when remote http host does not exist", () => {
        const node: INodeLabel = {id: "nodeId", node_id: "nodeId", node_name: "nodeName"};
        const headers = new Headers();
        headers.setPFHeader(Headers.CORRELATION_ID, "123");
        headers.setPFHeader(Headers.PROCESS_ID, "123");
        headers.setPFHeader(Headers.PARENT_ID, "");
        headers.setPFHeader(Headers.SEQUENCE_ID, "1");
        const msg = new JobMessage(node, headers.getRaw(), new Buffer(JSON.stringify({ val: "original" })));
        const worker = new HttpWorker({
            node_label: { id: "someId", node_id: "507f191e810c19729de860ea", node_name: "httpworker" },
            host: "non-existing-host",
            method: "post",
            port: 80,
            process_path: "/non-existing",
            status_path: "/status",
            secure: false,
            opts : {},
        });

        return worker.processData(msg)
            .then((outMsg: JobMessage) => {
                assert.equal(outMsg.getResult().code, ResultCode.HTTP_ERROR);
                assert.equal(outMsg.getContent(), JSON.stringify({ val: "original" }));
            });
    });

});
