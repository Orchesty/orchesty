import { assert } from "chai";
import "mocha";

import * as net from "net";
import TcpLimiter from "../../src/limiter/Limiter";
import Headers from "../../src/message/Headers";
import JobMessage from "../../src/message/JobMessage";

describe("Limiter", () => {
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

    it("check limit should returns true when missing mandatory message headers", async () => {
        const limiter = new TcpLimiter({host: "localhost", port: 3333});
        const msg = createBasicMessage();
        const resultOne = await limiter.canBeProcessed(msg);
        assert.isTrue(resultOne);

        msg.getHeaders().setPFHeader(Headers.LIMIT_KEY, "lkey");
        msg.getHeaders().setPFHeader(Headers.LIMIT_TIME, "ltime");
        // Headers.LIMIT_VAL is still missing
        const resultTwo = await limiter.canBeProcessed(msg);
        assert.isTrue(resultTwo);
    });

    it("check limit should return true when cannot contact remote server", async () => {
        const limiter = new TcpLimiter({host: "invalidhost", port: 3333});

        const msg = createBasicMessage();
        msg.getHeaders().setPFHeader(Headers.LIMIT_KEY, "lkey");
        msg.getHeaders().setPFHeader(Headers.LIMIT_TIME, "ltime");
        msg.getHeaders().setPFHeader(Headers.LIMIT_VALUE, "lvalue");

        const result = await limiter.canBeProcessed(msg);
        assert.isTrue(result);
    });

    //
    // Tests against real go app
    //

    it("isReady against live go server", async () => {
        const limiter = new TcpLimiter({host: "localhost", port: 3333});
        const result = await limiter.isReady();
        assert.isTrue(result);
    });

    it("check limit against live go server", async () => {
        const limiter = new TcpLimiter({host: "localhost", port: 3333});

        const msg = createBasicMessage();
        msg.getHeaders().setPFHeader(Headers.LIMIT_KEY, "lkey");
        msg.getHeaders().setPFHeader(Headers.LIMIT_TIME, "ltime");
        msg.getHeaders().setPFHeader(Headers.LIMIT_VALUE, "lvalue");

        const result = await limiter.canBeProcessed(msg);
        assert.isTrue(result);
    });

});

const createBasicMessage = (): JobMessage => {
    const hdrs = new Headers();
    hdrs.setPFHeader(Headers.PROCESS_ID, "pid");
    hdrs.setPFHeader(Headers.PARENT_ID, "");
    hdrs.setPFHeader(Headers.SEQUENCE_ID, "1");
    hdrs.setPFHeader(Headers.CORRELATION_ID, "corr");

    return new JobMessage(
        { id: "id", node_id: "node_id", node_name: "node_name", topology_id: "topo"},
        hdrs.getRaw(),
        new Buffer(""),
    );
};
