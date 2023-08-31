import { assert } from "chai";
import * as mocha from "mocha";
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

const metricsMock = {
    send: async () => "sent",
    addTag: () => { return; },
    removeTag: () => { return; },
};

const closeServer = (srv: http.Server): Promise<void> => {
    return new Promise((resolve) => {
        srv.close((err) => {
            console.log("\n\nclosing srv", err, "\n\n");
            resolve();
        });
    });
};

const createHttpWorker = (port: number, processPath: string, host: string = "localhost") => {
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
};

const createMessage = () => {
    const node: INodeLabel = {id: "nodeId", node_id: "nodeId", node_name: "nodeName", topology_id: "topoId"};
    const headers = new Headers();
    headers.setPFHeader(Headers.CORRELATION_ID, "123");
    headers.setPFHeader(Headers.PROCESS_ID, "123");
    headers.setPFHeader(Headers.PARENT_ID, "");
    headers.setPFHeader(Headers.SEQUENCE_ID, "1");

    return new JobMessage(node, headers.getRaw(), Buffer.from(JSON.stringify({ val: "original" })));
};

xdescribe("HttpWorker", () => {
    const port = 4020;
    let listener: http.Server;

    before(async () => {
        return new Promise((resolve) => {
            listener = httpServer.listen(port, () => { resolve(); });
        });
    });

    after(() => {
        closeServer(listener);
    });

    it("should convert JobMessage to http request, receive response and set message result #unit", async () => {
        const worker = createHttpWorker(port, "/ok");
        const msg = createMessage();

        const outMsgs = await worker.processData(msg);
        assert.lengthOf(outMsgs, 1);

        const outMsg: JobMessage = outMsgs[0];
        assert.equal(outMsg.getResult().code, ResultCode.SUCCESS);
        assert.equal(outMsg.getContent(), JSON.stringify({ val: "modified" }));
    });

    it("should return original message content when server responds with error #unit", async () => {
        const worker = createHttpWorker(port, "/invalid-status-code");
        const msg = createMessage();

        try {
            await worker.processData(msg);
            assert.fail("Should have failed");
        } catch (err) {
            assert.lengthOf(err, 1);
            const outMsg: JobMessage = err[0];

            assert.equal(outMsg.getResult().code, ResultCode.HTTP_ERROR);
            assert.equal(outMsg.getContent(), JSON.stringify({ val: "original" }));
        }
    });

    it("should return modified message but be marked as failed due to result_status error #unit", async () => {
        const worker = createHttpWorker(port, "/invalid-result-code");
        const msg = createMessage();

        const outMsgs = await worker.processData(msg);
        assert.lengthOf(outMsgs, 1);

        const outMsg: JobMessage = outMsgs[0];
        assert.equal(outMsg.getResult().code, ResultCode.MISSING_RESULT_CODE);
        assert.equal(outMsg.getContent(), JSON.stringify({ val: "original" }));
    });

    it("should return original message content when process_path does not exist #unit", async () => {
        const worker = createHttpWorker(port, "/non-existing");
        const msg = createMessage();

        try {
            await worker.processData(msg);
            assert.fail("Should have failed");
        } catch (err) {
            assert.lengthOf(err, 1);
            const outMsg: JobMessage = err[0];

            assert.equal(outMsg.getResult().code, ResultCode.HTTP_ERROR);
            assert.equal(outMsg.getContent(), JSON.stringify({ val: "original" }));
        }
    });

    it("should return orig msg content and set repeat code when worker timeouted on valid route #unit", async () => {
        const msg = createMessage();
        const worker = createHttpWorker(port, "/ok");
        // This should emit ETIMEDOUT error "/ok" responds after 20ms
        worker.setTimeout(5);

        try {
            await worker.processData(msg);
            assert.fail("Should have failed");
        } catch (err) {
            assert.lengthOf(err, 1);
            const outMsg: JobMessage = err[0];

            assert.equal(outMsg.getResult().code, ResultCode.REPEAT);
            assert.equal(outMsg.getContent(), JSON.stringify({ val: "original" }));
            assert.isTrue(outMsg.getHeaders().hasPFHeader(Headers.REPEAT_MAX_HOPS));
            assert.isTrue(outMsg.getHeaders().hasPFHeader(Headers.REPEAT_HOPS));
            assert.isTrue(outMsg.getHeaders().hasPFHeader(Headers.REPEAT_INTERVAL));
        }
    });

    it("should return empty data when worker returns empty body #unit", async () => {
        const worker = createHttpWorker(port, "/empty-result-body");
        const msg = createMessage();
        // TODO - find out why this test fails when using agent with keepAlive: true
        worker.setAgent(new http.Agent({ keepAlive: false }));

        const outMsgs = await worker.processData(msg);
        assert.lengthOf(outMsgs, 1);

        const outMsg: JobMessage = outMsgs[0];
        assert.equal(outMsg.getResult().code, ResultCode.SUCCESS);
        assert.equal(outMsg.getContent(), "");
    });

    it("should return that worker is ready when it is really ready #unit", async () => {
        const workerServer = express();
        workerServer.get("/status", (req, resp) => {
            resp.sendStatus(200);
        });
        const wsSrv = workerServer.listen(4030);

        const worker = createHttpWorker(4030, "/some-path");
        assert.isTrue(await worker.isWorkerReady());
        await closeServer(wsSrv);
    });

    it("should return that worker is not ready when it says it is not #unit", async () => {
        const workerServer = express();
        workerServer.post("/status", (req, resp) => {
            resp.sendStatus(500);
        });
        const wsSrv = workerServer.listen(4030);

        const worker = createHttpWorker(4030, "/some-path");
        assert.isFalse(await worker.isWorkerReady());
        await closeServer(wsSrv);
    });

    it("should send json and receive xml #unit", async () => {
        const worker = createHttpWorker(port, "/ok-xml");
        const msg = createMessage();

        const outMsgs = await worker.processData(msg);
        assert.lengthOf(outMsgs, 1);
        const outMsg: JobMessage = outMsgs[0];

        assert.equal(outMsg.getResult().code, ResultCode.SUCCESS);
        assert.equal(
            outMsg.getContent(),
            "<?xml version=\"1.0\" encoding=\"UTF-8\"?><root>some content</root>",
        );
    });

    it("should return failed result message when remote http host does not exist #unit", async () => {
        const msg = createMessage();
        const worker = createHttpWorker(port, "/non-existing", "nonexistinghost");

        try {
            await worker.processData(msg);
            assert.fail("Should have failed");
        } catch (err) {
            assert.lengthOf(err, 1);
            const outMsg: JobMessage = err[0];

            assert.equal(outMsg.getResult().code, ResultCode.HTTP_ERROR);
            assert.equal(outMsg.getContent(), JSON.stringify({ val: "original" }));
        }
    }).timeout(5000);

});
