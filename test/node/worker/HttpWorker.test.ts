import { assert } from "chai";
import "mocha";

import * as bodyParser from "body-parser";
import * as express from "express";
import Headers from "../../../src/message/Headers";
import {PFHeaders} from "../../../src/message/HeadersEnum";
import JobMessage from "../../../src/message/JobMessage";
import {ResultCode} from "../../../src/message/ResultCode";
import HttpWorker from "../../../src/node/worker/HttpWorker";
import {INodeLabel} from "../../../src/topology/Configurator";

const httpServer = express();
httpServer.use(bodyParser.json());
httpServer.post("/ok", (req, resp) => {
    assert.deepEqual(req.body, { val: "original" });
    assert.equal(req.headers.pf_node_name, "httpworker");
    assert.equal(req.headers.pf_node_id, "507f191e810c19729de860ea");
    resp.set({
        pf_result_code: 0,
        pf_result_message: "ok",
    });
    resp.status(200).send(JSON.stringify({ val: "modified" }));
});
httpServer.post("/invalid-status-code", (req, resp) => {
    assert.deepEqual(req.body, { val: "original" });
    resp.set({
        pf_result_code: 4001,
        pf_result_message: "some error",
    });
    resp.status(500).send(JSON.stringify({ val: "modified but 500" }));
});
httpServer.post("/invalid-result-code", (req, resp) => {
    assert.deepEqual(req.body, { val: "original" });
    resp.set({
        pf_result_code: ResultCode.WORKER_TIMEOUT,
        pf_result_message: "some error",
    });
    resp.status(200).send(JSON.stringify({ val: "modified" }));
});
httpServer.post("/empty-result-body", (req, resp) => {
    assert.deepEqual(req.body, { val: "original" });
    resp.set({
        pf_result_code: ResultCode.SUCCESS,
        pf_result_message: "some error",
    });
    resp.status(200).send();
});
httpServer.listen(4020);

describe("HttpWorker", () => {
    it("should convert JobMessage to http request and receives response and sets message result", () => {
        const node: INodeLabel = {id: "nodeId", node_id: "nodeId", node_name: "nodeName"};
        const headers = new Headers();
        headers.setPFHeader(PFHeaders.CORRELATION_ID, "123");
        headers.setPFHeader(PFHeaders.PROCESS_ID, "123");
        headers.setPFHeader(PFHeaders.PARENT_ID, "");
        headers.setPFHeader(PFHeaders.SEQUENCE_ID, "1");
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
        headers.setPFHeader(PFHeaders.CORRELATION_ID, "123");
        headers.setPFHeader(PFHeaders.PROCESS_ID, "123");
        headers.setPFHeader(PFHeaders.PARENT_ID, "");
        headers.setPFHeader(PFHeaders.SEQUENCE_ID, "1");
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
        headers.setPFHeader(PFHeaders.CORRELATION_ID, "123");
        headers.setPFHeader(PFHeaders.PROCESS_ID, "123");
        headers.setPFHeader(PFHeaders.PARENT_ID, "");
        headers.setPFHeader(PFHeaders.SEQUENCE_ID, "1");
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
        headers.setPFHeader(PFHeaders.CORRELATION_ID, "123");
        headers.setPFHeader(PFHeaders.PROCESS_ID, "123");
        headers.setPFHeader(PFHeaders.PARENT_ID, "");
        headers.setPFHeader(PFHeaders.SEQUENCE_ID, "1");
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

    it("should return empty data and settings when worker returns empty body", () => {
        const node: INodeLabel = {id: "nodeId", node_id: "nodeId", node_name: "nodeName"};
        const headers = new Headers();
        headers.setPFHeader(PFHeaders.CORRELATION_ID, "123");
        headers.setPFHeader(PFHeaders.PROCESS_ID, "123");
        headers.setPFHeader(PFHeaders.PARENT_ID, "");
        headers.setPFHeader(PFHeaders.SEQUENCE_ID, "1");
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
                assert.equal(outMsg.getContent(), JSON.stringify({ data: {}, settings: {} }));
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

});
