import { assert } from "chai";
import "mocha";

import * as bodyParser from "body-parser";
import * as express from "express";
import JobMessage from "../../../src/message/JobMessage";
import {ResultCode} from "../../../src/message/ResultCode";
import HttpWorker from "../../../src/node/worker/HttpWorker";

const httpServer = express();
httpServer.use(bodyParser.json());
httpServer.post("/ok", (req, resp) => {
    assert.deepEqual(req.body, { val: "original" });
    resp.status(200).send(JSON.stringify({ val: "modified" }));
});
httpServer.post("/not-ok", (req, resp) => {
    assert.deepEqual(req.body, { val: "original" });
    resp.status(500).send(JSON.stringify({ val: "modified but 500" }));
});
httpServer.listen(4020);

describe("HttpWorker", () => {
    it("should convert JobMessage to http request and receives response and sets message result", () => {
        const msg = new JobMessage("123", "123", 1, {}, JSON.stringify({ val: "original" }));
        const worker = new HttpWorker({
            node_id: "someId",
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
                assert.equal(outMsg.getResult().status, ResultCode.SUCCESS);
                assert.equal(outMsg.getContent(), JSON.stringify({ val: "modified" }));
            });
    });

    it("should return original message content when server responds with error", () => {
        const msg = new JobMessage("123", "123", 1, {}, JSON.stringify({ val: "original" }));
        const worker = new HttpWorker({
            node_id: "someId",
            host: "localhost",
            method: "post",
            port: 4020,
            process_path: "/not-ok",
            status_path: "/status",
            secure: false,
            opts : {},
        });

        return worker.processData(msg)
            .then((outMsg: JobMessage) => {
                assert.equal(outMsg.getResult().status, ResultCode.HTTP_ERROR);
                assert.equal(outMsg.getContent(), JSON.stringify({ val: "original" }));
            });
    });

    it("should return original message content when process_path does not exist", () => {
        const msg = new JobMessage("123", "123", 1, {}, JSON.stringify({ val: "original" }));
        const worker = new HttpWorker({
            node_id: "someId",
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
                assert.equal(outMsg.getResult().status, ResultCode.HTTP_ERROR);
                assert.equal(outMsg.getContent(), JSON.stringify({ val: "original" }));
            });
    });

    it("should return that worker is ready when it is really ready", () => {
        const workerServer = express();
        workerServer.get("/status", (req, resp) => {
            resp.sendStatus(200);
        });
        workerServer.listen(4321);

        const worker = new HttpWorker({
            node_id: "someId",
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
            node_id: "someId",
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
