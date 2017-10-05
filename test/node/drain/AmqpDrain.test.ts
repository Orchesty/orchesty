import { assert } from "chai";
import "mocha";

import AssertionPublisher from "lib-nodejs/dist/src/rabbitmq/AssertPublisher";
import * as mock from "ts-mockito";
import JobMessage from "../../../src/message/JobMessage";
import CounterPublisher from "../../../src/node/drain/amqp/CounterPublisher";
import FollowersPublisher from "../../../src/node/drain/amqp/FollowersPublisher";
import AmqpDrain, {IAmqpDrainSettings} from "../../../src/node/drain/AmqpDrain";

const settings: IAmqpDrainSettings = {
    node_id: "test-amqpdrain",
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
        const msg: JobMessage = new JobMessage("nid", "123", "123", "", 1, {}, body);

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
        const msg: JobMessage = new JobMessage("nid", "123", "123", "", 1, {}, body);

        return drain.forwardPart(msg)
            .then(() => {
                assert.isTrue(true);
            });
    });
});
