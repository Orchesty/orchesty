import { assert } from "chai";
import "mocha";

import Headers from "../../../src/message/Headers";
import JobMessage from "../../../src/message/JobMessage";
import HttpXmlParserWorker, {IHttpXmlParserWorkerSettings} from "../../../src/node/worker/HttpXmlParserWorker";
import {INodeLabel} from "../../../src/topology/Configurator";

const metricsMock = {
    send: () => Promise.resolve("sent"),
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
    port: 4020,
    process_path: "/ok",
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

});
