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
        const msg = new JobMessage("123", 1, {}, JSON.stringify({ val: "original" }));
        const worker = new HttpWorker({
            method: "post",
            url: "http://localhost:4020/ok",
            opts : {},
        });
        return worker.processData(msg)
            .then((outMsg: JobMessage) => {
                assert.equal(outMsg.getResult().status, ResultCode.SUCCESS);
                assert.equal(outMsg.getContent(), JSON.stringify({ val: "modified" }));
            });
    });

    it("should return original message content when server responds with error", () => {
        const msg = new JobMessage("123", 1, {}, JSON.stringify({ val: "original" }));
        const worker = new HttpWorker({
            method: "post",
            url: "http://localhost:4020/not-ok",
            opts : {},
        });
        return worker.processData(msg)
            .then((outMsg: JobMessage) => {
                assert.equal(outMsg.getResult().status, ResultCode.HTTP_ERROR);
                assert.equal(outMsg.getContent(), JSON.stringify({ val: "original" }));
            });
    });

    it("should return original message content when server route does not exist", () => {
        const msg = new JobMessage("123", 1, {}, JSON.stringify({ val: "original" }));
        const worker = new HttpWorker({
            method: "post",
            url: "http://localhost:4020/non-existing",
            opts : {},
        });
        return worker.processData(msg)
            .then((outMsg: JobMessage) => {
                assert.equal(outMsg.getResult().status, ResultCode.HTTP_ERROR);
                assert.equal(outMsg.getContent(), JSON.stringify({ val: "original" }));
            });
    });

});
