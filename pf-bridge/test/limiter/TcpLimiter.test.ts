import { assert } from "chai";
import "mocha";

import * as net from "net";
import TcpLimiter from "../../src/limiter/TcpLimiter";

describe("TcpLimiter", () => {
    it("isReady should return negative result on requesting invalid limiter", async () => {
        const limiter = new TcpLimiter({host: "invalidhost", port: 3333});
        const result = await limiter.isReady();
        assert.isFalse(result);
    });

    it("isReady should send and receive tcp packet", (done) => {
        const server = net.createServer((socket) => {
            socket.write("ok");
            socket.pipe(socket);
        });
        server.listen(1337, "localhost");

        setTimeout( async () => {
            const limiter = new TcpLimiter({host: "localhost", port: 1337});
            const result = await limiter.isReady();
            assert.isTrue(result);
            done();
        }, 100);
    });

    it("isReady against live go server", async () => {
        const limiter = new TcpLimiter({host: "localhost", port: 3333});
        const result = await limiter.isReady();
        assert.isTrue(result);
    });

});
