import { assert } from "chai";
import "mocha";

import * as net from "net";
import TcpLimiter from "../../src/limiter/TcpLimiter";

describe("TcpLimiter", () => {
    it("isReady should return negative result on requesting invalid limiter", async () => {
        const limiter = new TcpLimiter({host: "invalidhost", port: 65336});
        const result = await limiter.isReady();
        assert.isFalse(result);
    });

    it("isReady should send and receive tcp packet", (done) => {
        const server = net.createServer((socket) => {
            socket.write("pf-health-check;someid;ok");
            socket.pipe(socket);
        });
        server.listen(65337, "localhost");

        setTimeout( async () => {
            const limiter = new TcpLimiter({host: "localhost", port: 65337});
            const result = await limiter.isReady();
            assert.isTrue(result);
            done();
        }, 300);
    });

});
