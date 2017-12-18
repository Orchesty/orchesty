import { assert } from "chai";
import "mocha";

import * as bodyParser from "body-parser";
import * as express from "express";
import * as rp from "request-promise";
import Headers from "../../../src/message/Headers";
import Terminator from "../../../src/topology/terminator/Terminator";

const dumbStorage = {
    has: () => Promise.resolve(false),
    hasSome: () => Promise.resolve(false),
    get: () => Promise.resolve(null),
    add: () => Promise.resolve(true),
    remove: () => Promise.resolve(true),
};

describe("Terminator", () => {
    it("should accept valid termination http request and send another request to given url", async () => {
        const prom = new Promise((resolve) => {
            const topoApiMock = express();
            topoApiMock.use(bodyParser.raw({ type: () => true }));
            topoApiMock.get("/remote-terminate", (req) => {
                assert.deepEqual(req.body, {});
                resolve();
            });
            topoApiMock.listen(7900);
        });

        const terminator = new Terminator(7901, dumbStorage);
        await terminator.startServer();

        const headers = new Headers();
        headers.setPFHeader(Headers.TOPOLOGY_DELETE_URL, "http://localhost:7900/remote-terminate");

        const resp = await rp({
            uri: `http://localhost:7901/topology/terminate/someTopoId`,
            headers: headers.getRaw(),
        });

        assert.equal(resp, "Topology will be terminated as soon as possible.");

        return prom;
    });

    it("should return error response when missing delete url header", async () => {
        const terminator = new Terminator(7902, dumbStorage);
        await terminator.startServer();

        try {
            await rp({uri: `http://localhost:7902/topology/terminate/abc`});
        } catch (e) {
            assert.equal(e.statusCode, 400);
            return Promise.resolve();
        }
    });

});
