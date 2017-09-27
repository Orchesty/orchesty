import { assert } from "chai";
import "mocha";

import {Channel} from "amqplib";
import Connection from "lib-nodejs/dist/src/rabbitmq/Connection";
import Publisher from "lib-nodejs/dist/src/rabbitmq/Publisher";
import {amqpConnectionOptions} from "../../../src/config";
import JobMessage from "../../../src/message/JobMessage";
import {default as AmqpFaucet, IAmqpFaucetSettings} from "../../../src/node/faucet/AmqpFaucet";
import {FaucetProcessMsgFn} from "../../../src/node/faucet/IFaucet";

const settings: IAmqpFaucetSettings = {
    node_id: "amqpFaucetNodeId",
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
    it("should start consumption on open", () => {
        const check = (msg: JobMessage) => {
            assert.equal(msg.getSequenceId(), 999);
            assert.equal(msg.getProcessId(), "a23");
        };
        const faucet = new AmqpFaucet(settings, conn);

        const processFn: FaucetProcessMsgFn = (msg: JobMessage) => {
            check(msg);
            return Promise.resolve(msg);
        };

        return faucet.open(processFn)
            .then(() => {
                // send message to exchange via which it should be routed to amqpFaucet's input queue
                return publisher.publish(
                    settings.exchange.name,
                    settings.routing_key,
                    new Buffer(JSON.stringify({data: "test", settings: {}})),
                    { headers: { job_id: "a23", sequence_id: 999 } },
                );
            });
    });
});
