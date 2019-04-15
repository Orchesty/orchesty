import { assert } from "chai";
import "mocha";

import {Channel} from "amqplib";
import {Connection} from "amqplib-plus/dist/lib/Connection";
import {Publisher} from "amqplib-plus/dist/lib/Publisher";
import {amqpConnectionOptions} from "../../../src/config";
import Headers from "../../../src/message/Headers";
import JobMessage from "../../../src/message/JobMessage";
import {default as AmqpFaucet, IAmqpFaucetSettings} from "../../../src/node/faucet/AmqpFaucet";
import {FaucetProcessMsgFn} from "../../../src/node/faucet/IFaucet";

const settings: IAmqpFaucetSettings = {
    node_label: {
        id: "amqpFaucetNodeId",
        node_id: "507f191e810c19729de860ea",
        node_name: "faucet-amqp",
        topology_id: "topoId",
    },
    exchange: {
        name: "amqp_faucet_test_ex",
        type: "direct",
        options: {},
    },
    queue: {
        name: "amqp_faucet_test_q",
        options: {},
    },
    prefetch: 1,
    dead_letter_exchange: {
        name: "amqp_faucet_test_dl_ex",
        type: "direct",
        options: {},
    },
    routing_key: "amqp_faucet_test_rk",
};
const conn = new Connection(amqpConnectionOptions);

// Publisher emulates the previous node in topology
const publisher = new Publisher(conn, (ch: Channel) =>  Promise.resolve() );

describe("AmqpFaucet", () => {
    it("should start consumption on open", (done) => {
        const check = (msg: JobMessage) => {
            assert.equal(msg.getCorrelationId(), "correlationId");
            assert.equal(msg.getProcessId(), "processId");
            assert.equal(msg.getSequenceId(), 0);
            assert.equal(msg.getParentId(), "");
            assert.equal(msg.getHeaders().getHeader("content-type"), "text/plain");
            done();
        };
        const faucet = new AmqpFaucet(settings, conn);

        const processFn: FaucetProcessMsgFn = (msg: JobMessage) => {
            check(msg);

            return Promise.resolve();
        };

        faucet.open(processFn)
            .then(() => {
                const headers = new Headers();
                headers.setPFHeader(Headers.CORRELATION_ID, "correlationId");
                headers.setPFHeader(Headers.PROCESS_ID, "processId");
                headers.setPFHeader(Headers.SEQUENCE_ID, "0");
                headers.setPFHeader(Headers.PARENT_ID, "");

                // send message to exchange via which it should be routed to amqpFaucet's input queue
                return publisher.publish(
                    settings.exchange.name,
                    settings.routing_key,
                    Buffer.from("Test content"),
                    {
                        contentType: "text/plain",
                        headers: headers.getRaw(),
                    },
                );
            });
    });
});
