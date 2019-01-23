import { assert } from "chai";
import "mocha";

import {AssertionPublisher} from "amqplib-plus/dist/lib/AssertPublisher";
import * as mock from "ts-mockito";
import {persistentMessages} from "../../../src/config";
import Headers from "../../../src/message/Headers";
import JobMessage from "../../../src/message/JobMessage";
import {ResultCode} from "../../../src/message/ResultCode";
import CounterPublisher from "../../../src/node/drain/amqp/CounterPublisher";
import FollowersPublisher from "../../../src/node/drain/amqp/FollowersPublisher";
import AmqpDrain, {IAmqpDrainSettings} from "../../../src/node/drain/AmqpDrain";
import {INodeLabel} from "../../../src/topology/Configurator";

const metricsMock = {
    send: () => Promise.resolve("sent"),
    addTag: () => { return; },
    removeTag: () => { return; },
};

const settings: IAmqpDrainSettings = {
    node_label: {
        id: "test-amqpdrain",
        node_id: "507f191e810c19729de860ea",
        node_name: "test",
        topology_id: "topoId",
    },
    persistent: persistentMessages,
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
            name:  "input_queue",
            options: {},
        },
    },
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

/**
 *
 * @return {JobMessage}
 */
function createMockMessage(): JobMessage {
    const body = new Buffer(JSON.stringify({foo: "bar"}));
    const node: INodeLabel = {id: "nodeId", node_id: "nodeId", node_name: "nodeName", topology_id: "topoId"};
    const headers = new Headers();
    headers.setPFHeader(Headers.CORRELATION_ID, "123");
    headers.setPFHeader(Headers.PROCESS_ID, "123");
    headers.setPFHeader(Headers.PARENT_ID, "");
    headers.setPFHeader(Headers.SEQUENCE_ID, "1");
    headers.setHeader(Headers.CONTENT_TYPE, "application/json");

    return new JobMessage(node, headers.getRaw(), body);
}

const followPubFailMock: FollowersPublisher = mock.mock(FollowersPublisher);
followPubFailMock.send = () => {
    assert.fail();
    return Promise.resolve(0);
};

const nonStandardPubFailMock: AssertionPublisher = mock.mock(AssertionPublisher);
nonStandardPubFailMock.sendToQueue = () => {
    assert.fail();
    return Promise.resolve();
};

const counterPubFailMock: CounterPublisher = mock.mock(CounterPublisher);
counterPubFailMock.send = () => {
    assert.fail();
    return Promise.resolve();
};

describe("AmqpDrain", () => {
    it("should forward message and send to counter when message has success result code", (done) => {
        let counterMsgSent = false;
        let forwardMsgSent = false;

        const msg = createMockMessage();
        msg.setResult({code: ResultCode.SUCCESS, message: "ok"});

        const validateTest = () => {
            if (counterMsgSent && forwardMsgSent) {
                done();
            }
        };

        const counterPub: CounterPublisher = mock.mock(CounterPublisher);
        counterPub.send = (jm: JobMessage) => {
            assert.equal(msg, jm);
            counterMsgSent = true;
            validateTest();

            return Promise.resolve();
        };
        const followPub: FollowersPublisher = mock.mock(FollowersPublisher);
        followPub.send = (jm: JobMessage) => {
            assert.equal(msg, jm);
            forwardMsgSent = true;
            validateTest();

            return Promise.resolve(0);
        };

        const drain = new AmqpDrain(settings, counterPub, followPub, nonStandardPubFailMock, metricsMock);

        drain.forward(msg);
    });

    it("should forward message only to counter when message has error result code", () => {
        const msg = createMockMessage();
        msg.setResult({ code: ResultCode.UNKNOWN_ERROR, message: "Unknown error"});

        const counterPub: CounterPublisher = mock.mock(CounterPublisher);
        counterPub.send = (toCounter: JobMessage) => {
            assert.equal(msg, toCounter);
            return Promise.resolve();
        };

        const drain = new AmqpDrain(settings, counterPub, followPubFailMock, nonStandardPubFailMock, metricsMock);

        drain.forward(msg);
    });

    it("should forward message only to counter when message has invalid non-standard code", () => {
        const msg = createMockMessage();
        msg.setResult({ code: 1111, message: "Invalid non-standard code"});

        const counterPub: CounterPublisher = mock.mock(CounterPublisher);
        counterPub.send = (toCounter: JobMessage) => {
            assert.equal(toCounter.getResult().code, ResultCode.INVALID_NON_STANDARD_CODE);
            assert.equal(toCounter.getResult().message, "Unknown non-standard result code '1111'");
            return Promise.resolve();
        };

        const nonStandardPub: AssertionPublisher = mock.mock(AssertionPublisher);

        const drain = new AmqpDrain(settings, counterPub, followPubFailMock, nonStandardPub, metricsMock);

        drain.forward(msg);
    });

    it("should send counter error message on repeat message with missing repeat interval", () => {
        const msg = createMockMessage();
        msg.setResult({ code: ResultCode.REPEAT, message: "repeat please"});
        msg.getHeaders().setPFHeader(Headers.REPEAT_HOPS, "1");
        msg.getHeaders().setPFHeader(Headers.REPEAT_MAX_HOPS, "10");

        const counterPub: CounterPublisher = mock.mock(CounterPublisher);
        counterPub.send = (toCounter: JobMessage) => {
            assert.strictEqual(toCounter, msg);
            assert.equal(ResultCode.REPEAT_INVALID_INTERVAL, toCounter.getResult().code);
            return Promise.resolve();
        };

        const drain = new AmqpDrain(settings, counterPub, followPubFailMock, nonStandardPubFailMock, metricsMock);

        drain.forward(msg);
    });

    it("should send counter error message on repeat message with exceeded hops", () => {
        const msg = createMockMessage();
        msg.setResult({ code: ResultCode.REPEAT, message: "repeat please"});

        msg.getHeaders().setPFHeader(Headers.REPEAT_INTERVAL, "1000");
        msg.getHeaders().setPFHeader(Headers.REPEAT_MAX_HOPS, "5");
        msg.getHeaders().setPFHeader(Headers.REPEAT_HOPS, "6");

        const counterPub: CounterPublisher = mock.mock(CounterPublisher);
        counterPub.send = (toCounter: JobMessage) => {
            assert.strictEqual(toCounter, msg);
            assert.equal(ResultCode.REPEAT_MAX_HOPS_REACHED, toCounter.getResult().code);
            return Promise.resolve();
        };

        const drain = new AmqpDrain(settings, counterPub, followPubFailMock, nonStandardPubFailMock, metricsMock);

        drain.forward(msg);
    });

    it("should forward message only via non-standard publisher on repeat result code", () => {
        const msg = createMockMessage();
        msg.setResult({ code: ResultCode.REPEAT, message: "repeat please"});

        msg.getHeaders().setPFHeader(Headers.REPEAT_INTERVAL, "1000");
        msg.getHeaders().setPFHeader(Headers.REPEAT_MAX_HOPS, "5");
        msg.getHeaders().setPFHeader(Headers.REPEAT_HOPS, "1");

        const nonStandardPub: AssertionPublisher = mock.mock(AssertionPublisher);
        nonStandardPub.sendToQueue = (queue: string, body: Buffer, options: any) => {
            assert.equal(queue, settings.repeater.queue.name);
            assert.equal(body.toString(), msg.getContent());

            const originalHeaders = new Headers(msg.getHeaders().getRaw());
            // this header should be added to repeater message by forwarder
            originalHeaders.setPFHeader(Headers.REPEAT_QUEUE, settings.faucet.queue.name);

            assert.deepEqual(options.headers, originalHeaders.getRaw());

            return Promise.resolve();
        };

        const drain = new AmqpDrain(settings, counterPubFailMock, followPubFailMock, nonStandardPub, metricsMock);

        drain.forward(msg);
    });

    it("should forward message directly to node's input queue n repeat code with small repeat interval", () => {
        const msg = createMockMessage();
        msg.setResult({ code: ResultCode.REPEAT, message: "repeat please"});

        msg.getHeaders().setPFHeader(Headers.REPEAT_INTERVAL, "0");
        msg.getHeaders().setPFHeader(Headers.REPEAT_MAX_HOPS, "5");
        msg.getHeaders().setPFHeader(Headers.REPEAT_HOPS, "1");

        const nonStandardPub: AssertionPublisher = mock.mock(AssertionPublisher);
        nonStandardPub.sendToQueue = (queue: string, body: Buffer, options: any) => {
            assert.equal(queue, settings.faucet.queue.name);
            assert.equal(body.toString(), msg.getContent());
            assert.deepEqual(options.headers, msg.getHeaders().getRaw());

            return Promise.resolve();
        };

        const drain = new AmqpDrain(settings, counterPubFailMock, followPubFailMock, nonStandardPub, metricsMock);

        drain.forward(msg);
    });

    it("forwardPart should send single message only to followers but not to counter", () => {
        const msg: JobMessage = createMockMessage();
        msg.setResult({code: ResultCode.SUCCESS, message: "partial ok"});

        const followPub: FollowersPublisher = mock.mock(FollowersPublisher);
        followPub.send = (jm: JobMessage) => {
            assert.strictEqual(msg, jm);
            assert.equal(jm.getResult().code, ResultCode.SUCCESS);

            return Promise.resolve(0);
        };

        const drain = new AmqpDrain(settings, counterPubFailMock, followPub, nonStandardPubFailMock, metricsMock);

        return drain.forwardPart(msg)
            .then(() => {
                assert.isTrue(true);
            });
    });

});
