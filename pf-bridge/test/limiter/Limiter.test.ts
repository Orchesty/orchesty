import { assert } from "chai";
import "mocha";

import * as net from "net";
import Limiter from "../../src/limiter/Limiter";
import TcpClient from "../../src/limiter/TcpClient";
import Headers from "../../src/message/Headers";
import JobMessage from "../../src/message/JobMessage";

const createBasicMessage = (): JobMessage => {
    const hdrs = new Headers();
    hdrs.setPFHeader(Headers.PROCESS_ID, "pid");
    hdrs.setPFHeader(Headers.PARENT_ID, "");
    hdrs.setPFHeader(Headers.SEQUENCE_ID, "1");
    hdrs.setPFHeader(Headers.CORRELATION_ID, "corr");

    return new JobMessage(
        { id: "id", node_id: "node_id", node_name: "node_name", topology_id: "topo"},
        hdrs.getRaw(),
        Buffer.from(""),
    );
};

xdescribe("Limiter", () => {
    it("isReady should return negative result on requesting invalid limiter #unit", async () => {
        const tcp = new TcpClient("invalidhost", 3333);
        const publisher: any = {};
        const limiter = new Limiter(tcp, publisher);
        const result = await limiter.isReady();
        assert.isFalse(result);
    });

    it("isReady should send and receive tcp packet #unit", (done) => {
        const server = net.createServer((socket) => {
            socket.write("pf-health-check;someid;ok");
        });
        server.listen(1337, "localhost");

        setTimeout( async () => {
            const tcp = new TcpClient("localhost", 1337);
            const publisher: any = {};
            const limiter = new Limiter(tcp, publisher);
            const result = await limiter.isReady();
            assert.isTrue(result);
            done();
        }, 100);
    });

    it("canBeProcessed should returns true when missing mandatory message headers #unit", async () => {
        const tcp = new TcpClient("localhost", 3333);
        const publisher: any = {};
        const limiter = new Limiter(tcp, publisher);
        const msg = createBasicMessage();
        const resultOne = await limiter.canBeProcessed(msg);
        assert.isTrue(resultOne);

        msg.getHeaders().setPFHeader(Headers.LIMIT_KEY, "lkey");
        msg.getHeaders().setPFHeader(Headers.LIMIT_TIME, "ltime");
        // Headers.LIMIT_VAL is still missing
        const resultTwo = await limiter.canBeProcessed(msg);
        assert.isTrue(resultTwo);
    });

    it("canBeProcessed should return true when cannot contact remote server #unit", async () => {
        const tcp = new TcpClient("invalidhost", 3333);
        const publisher: any = {};
        const limiter = new Limiter(tcp, publisher);

        const msg = createBasicMessage();
        msg.getHeaders().setPFHeader(Headers.LIMIT_KEY, "lkey");
        msg.getHeaders().setPFHeader(Headers.LIMIT_TIME, "ltime");
        msg.getHeaders().setPFHeader(Headers.LIMIT_VALUE, "lvalue");

        const result = await limiter.canBeProcessed(msg);
        assert.isTrue(result);
    }).timeout(4000);

    it("canBeProcessed should return what true when limiter returns positive response #unit", (done) => {
        const positive = net.createServer((socket) => {
            socket.write("pf-check;someid;ok");
        });
        positive.listen(1338, "localhost", () => {
            assert.isTrue(positive.listening);
            const tcp = new TcpClient("localhost", 1338);
            const publisher: any = {};
            const limiter = new Limiter(tcp, publisher);

            const msg = createBasicMessage();
            msg.getHeaders().setPFHeader(Headers.LIMIT_KEY, "lkey");
            msg.getHeaders().setPFHeader(Headers.LIMIT_TIME, "ltime");
            msg.getHeaders().setPFHeader(Headers.LIMIT_VALUE, "lvalue");

            // wait a while until server really ready
            setTimeout(async () => {
                assert.isTrue(await limiter.canBeProcessed(msg));
                done();
            }, 100);
        });
    });

    it("canBeProcessed should return what true when limiter returns negative response #unit", (done) => {
        const negative = net.createServer((socket) => {
            socket.write("pf-check;someid;nok");
        });
        negative.listen(1339, "localhost", async () => {
            assert.isTrue(negative.listening);

            const tcp = new TcpClient("localhost", 1339);
            const publisher: any = {};
            const limiter = new Limiter(tcp, publisher);

            const msg = createBasicMessage();
            msg.getHeaders().setPFHeader(Headers.LIMIT_KEY, "lkey");
            msg.getHeaders().setPFHeader(Headers.LIMIT_TIME, "ltime");
            msg.getHeaders().setPFHeader(Headers.LIMIT_VALUE, "lvalue");

            const result = await limiter.canBeProcessed(msg);
            assert.isFalse(result);

            done();
        });
    });

    //
    // Tests against real go app
    //

    it.skip("isReady against live go server #integration", async () => {
        const tcp = new TcpClient("limiter", 3333);
        const publisher: any = {};
        const limiter = new Limiter(tcp, publisher);
        const result = await limiter.isReady();
        assert.isTrue(result);
    });

    it("check limit against live go server #integration", async () => {
        const tcp = new TcpClient("limiter", 3333);
        const publisher: any = {};
        const limiter = new Limiter(tcp, publisher);

        const msg = createBasicMessage();
        msg.getHeaders().setPFHeader(Headers.LIMIT_KEY, "lkey");
        msg.getHeaders().setPFHeader(Headers.LIMIT_TIME, "ltime");
        msg.getHeaders().setPFHeader(Headers.LIMIT_VALUE, "lvalue");

        const result = await limiter.canBeProcessed(msg);
        assert.isTrue(result);
    });

});