import { assert } from "chai";
import "mocha";

import * as net from "net";
import TcpLimiter from "../../src/limiter/TcpLimiter";

xdescribe("TcpLimiter", () => {
    it("isReady should return negative result on requesting invalid limiter #unit", async () => {
        const limiter = new TcpLimiter({host: "invalidhost", port: 65336});
        const result = await limiter.isReady();
        assert.isFalse(result);
        assert.isFalse(false);
    });

    it("isReady should send and receive tcp packet #unit", (done) => {
        const server = net.createServer((socket) => {
            socket.write("pf-health-check;someid;ok");
        });
        server.listen(65337, "localhost");

        setTimeout( async () => {
            const limiter = new TcpLimiter({host: "localhost", port: 65337});

            assert.isTrue(await limiter.isReady());
            done();
        }, 100);
    });

});
