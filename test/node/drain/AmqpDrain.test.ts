import { assert } from "chai";
import "mocha";

import AssertionPublisher from "lib-nodejs/dist/src/rabbitmq/AssertPublisher";
import * as mock from "ts-mockito";
import Headers from "../../../src/message/Headers";
import {PFHeaders} from "../../../src/message/HeadersEnum";
import JobMessage from "../../../src/message/JobMessage";
import CounterPublisher from "../../../src/node/drain/amqp/CounterPublisher";
import FollowersPublisher from "../../../src/node/drain/amqp/FollowersPublisher";
import AmqpDrain, {IAmqpDrainSettings} from "../../../src/node/drain/AmqpDrain";
import {INodeLabel} from "../../../src/topology/Configurator";

const settings: IAmqpDrainSettings = {
    node_label: {
        id: "test-amqpdrain",
        node_id: "507f191e810c19729de860ea",
        node_name: "test",
    },
    counter: {
        queue: {
            name: "test-amqpdrain-counter",
            options: {},
        },
    },
    repeater: {
        queue: {
            name: "test-amqpdrain-repeater",
            options: {},
        },
    },
    faucet: {
        queue: {
            name:  "",
            options: {},
        },
    },
    resequencer: false,
    followers: [
        {
            node_id: "follower1",
            exchange: {
                name: "test-amqpdrain-follower-exchange",
                type: "direct",
                options: {},
            },
            queue: {
                name: "test-amqpdrain-follower-queue",
                options: {},
            },
            routing_key: "amqpdrain-RK",
        },
    ],
};

describe("AmqpDrain", () => {
    it("on success job forward() should forward message to followers and send message to counter", () => {
        const counterPub: CounterPublisher = mock.mock(CounterPublisher);
        counterPub.send = (jm: JobMessage) => Promise.resolve();
        const nonstandardPub: AssertionPublisher = mock.mock(AssertionPublisher);
        nonstandardPub.sendToQueue = () => Promise.resolve();
        const followPub: FollowersPublisher = mock.mock(FollowersPublisher);
        followPub.send = (jm: JobMessage) => Promise.resolve();
        const drain = new AmqpDrain(settings, counterPub, followPub, nonstandardPub);

        const body = new Buffer(JSON.stringify({data: "test", settings: {}}));
        const node: INodeLabel = {id: "nodeId", node_id: "nodeId", node_name: "nodeName"};
        const headers = new Headers();
        headers.setPFHeader(PFHeaders.CORRELATION_ID, "123");
        headers.setPFHeader(PFHeaders.PROCESS_ID, "123");
        headers.setPFHeader(PFHeaders.PARENT_ID, "");
        headers.setPFHeader(PFHeaders.SEQUENCE_ID, `1`);
        const msg: JobMessage = new JobMessage(node, headers.getRaw(), body);

        return drain.forward(msg)
            .then((result: JobMessage) => {
                assert.instanceOf(result, JobMessage);
            });
    });

    it("forwardPart() should send single message to followers only", () => {
        const counterPub: CounterPublisher = mock.mock(CounterPublisher);
        counterPub.send = () => {
            assert.fail();
            return Promise.resolve();
        };
        const nonstandardPub: AssertionPublisher = mock.mock(AssertionPublisher);
        nonstandardPub.sendToQueue = () => {
            assert.fail();
            return Promise.resolve();
        };

        const followPub: FollowersPublisher = mock.mock(FollowersPublisher);
        followPub.send = (jm: JobMessage) => Promise.resolve();

        const drain = new AmqpDrain(settings, counterPub, followPub, nonstandardPub);

        const body = new Buffer(JSON.stringify({data: "test", settings: {}}));
        const node: INodeLabel = {id: "nodeId", node_id: "nodeId", node_name: "nodeName"};
        const headers = new Headers();
        headers.setPFHeader(PFHeaders.CORRELATION_ID, "123");
        headers.setPFHeader(PFHeaders.PROCESS_ID, "123");
        headers.setPFHeader(PFHeaders.PARENT_ID, "");
        headers.setPFHeader(PFHeaders.SEQUENCE_ID, `1`);
        const msg: JobMessage = new JobMessage(node, headers.getRaw(), body);

        return drain.forwardPart(msg)
            .then(() => {
                assert.isTrue(true);
            });
    });
});
