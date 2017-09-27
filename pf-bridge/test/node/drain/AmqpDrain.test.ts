import { assert } from "chai";
import "mocha";

import * as mock from "ts-mockito";
import JobMessage from "../../../src/message/JobMessage";
import CounterPublisher from "../../../src/node/drain/amqp/CounterPublisher";
import FollowersPublisher from "../../../src/node/drain/amqp/FollowersPublisher";
import AmqpDrain, {IAmqpDrainSettings} from "../../../src/node/drain/AmqpDrain";

const settings: IAmqpDrainSettings = {
    node_id: "test-amqpdrain",
    counter_event: {
        queue: {
            name: "test-amqpdrain-counter",
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
    it("should forward to followers and send message to counter", () => {
        const counterPub: CounterPublisher = mock.mock(CounterPublisher);
        counterPub.send = (jm: JobMessage) => Promise.resolve();
        const followPub: FollowersPublisher = mock.mock(FollowersPublisher);
        followPub.send = (jm: JobMessage) => Promise.resolve();
        const drain = new AmqpDrain(settings, counterPub, followPub);

        const msg: JobMessage = new JobMessage(
            "nid", "123", "123", "", 1, {}, JSON.stringify({data: "test", settings: {}}),
        );

        return drain.forward(msg)
            .then((result: JobMessage) => {
                assert.instanceOf(result, JobMessage);
            });
    });
});
