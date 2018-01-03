import { assert } from "chai";
import "mocha";

import * as bodyParser from "body-parser";
import * as express from "express";
import Headers from "../../../src/message/Headers";
import JobMessage from "../../../src/message/JobMessage";
import {ResultCode} from "../../../src/message/ResultCode";
import HttpXmlParserWorker, {IHttpXmlParserWorkerSettings} from "../../../src/node/worker/HttpXmlParserWorker";
import {INodeLabel} from "../../../src/topology/Configurator";

const metricsMock = {
    send: () => Promise.resolve("sent"),
};

const httpServer = express();
const bodyParserRaw = {
    type: () => true,
};

const workerSettings: IHttpXmlParserWorkerSettings = {
    node_label: {
        id: "someId",
        node_id: "507f191e810c19729de860ea",
        node_name: "httpxmlparserworker",
        topology_id: "topoId",
    },
    host: "localhost",
    method: "post",
    port: 4030,
    process_path: "/xml-worker",
    status_path: "/status",
    secure: false,
    opts : {},
    parser_settings: {
        foo: "bar",
    },
};

describe("HttpXmlParserWorker", () => {
    it("should prepare POST body in correct format", () => {
        const node: INodeLabel = {id: "nodeId", node_id: "nodeId", node_name: "nodeName", topology_id: "topoId"};
        const headers = new Headers();
        headers.setPFHeader(Headers.CORRELATION_ID, "123");
        headers.setPFHeader(Headers.PROCESS_ID, "123");
        headers.setPFHeader(Headers.PARENT_ID, "");
        headers.setPFHeader(Headers.SEQUENCE_ID, "1");
        headers.setHeader("content-type", "application/xml");

        const msg = new JobMessage(
            node,
            headers.getRaw(),
            new Buffer("<?xml version=\"1.0\" encoding=\"UTF-8\"?><root></root>"),
        );

        const worker = new HttpXmlParserWorker(workerSettings, metricsMock);

        const body = worker.getHttpRequestBody(msg);
        const bodyJson = JSON.parse(body);

        assert.deepEqual(bodyJson.settings, workerSettings.parser_settings);
        assert.equal(bodyJson.data, '<?xml version="1.0" encoding="UTF-8"?><root></root>');
    });

    it("sends request to remote server", async () => {
        httpServer.use(bodyParser.raw(bodyParserRaw));
        httpServer.post("/xml-worker", (req, resp) => {
            assert.equal(
                req.body.toString(),
                '{"data":"<?xml version=\\"1.0\\" encoding=\\"UTF-8\\"?><root></root>","settings":{"foo":"bar"}}',
            );
            assert.equal(req.headers["pf-node-name"], "httpxmlparserworker");
            assert.equal(req.headers["pf-node-id"], "507f191e810c19729de860ea");

            const requestHeaders: any = req.headers;
            const replyHeaders = new Headers(requestHeaders);
            replyHeaders.setPFHeader(Headers.RESULT_CODE, `${ResultCode.SUCCESS}`);
            replyHeaders.setPFHeader(Headers.RESULT_MESSAGE, "ok");

            resp.set(replyHeaders.getRaw());
            resp.status(200).send(JSON.stringify({ val: "parser-output" }));
        });
        httpServer.listen(4030);

        const worker = new HttpXmlParserWorker(workerSettings, metricsMock);

        const node: INodeLabel = {id: "nodeId", node_id: "nodeId", node_name: "nodeName", topology_id: "topoId"};
        const headers = new Headers();
        headers.setPFHeader(Headers.CORRELATION_ID, "123");
        headers.setPFHeader(Headers.PROCESS_ID, "123");
        headers.setPFHeader(Headers.PARENT_ID, "");
        headers.setPFHeader(Headers.SEQUENCE_ID, "1");
        headers.setHeader("content-type", "application/xml");

        const msg = new JobMessage(
            node,
            headers.getRaw(),
            new Buffer("<?xml version=\"1.0\" encoding=\"UTF-8\"?><root></root>"),
        );

        const outMsg = await worker.processData(msg);
        assert.equal(outMsg[0].getResult().code, ResultCode.SUCCESS);
        assert.equal(outMsg[0].getResult().message, "ok");
        assert.equal(outMsg[0].getContent(), JSON.stringify({ val: "parser-output" }));
    });

});
