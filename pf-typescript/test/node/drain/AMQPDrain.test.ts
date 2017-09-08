import { assert } from "chai";
import "mocha";

import * as mock from "ts-mockito";
import JobMessage from "../../../src/message/JobMessage";
import CounterPublisher from "../../../src/node/drain/amqp/CounterPublisher";
import FollowersPublisher from "../../../src/node/drain/amqp/FollowersPublisher";
import AMQPDrain, {IAmqpDrainSettings} from "../../../src/node/drain/AMQPDrain";

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

describe("AMQPDrain", () => {
    it("should forward to counter and followers on open", () => {
        const counterPub: CounterPublisher = mock.mock(CounterPublisher);
        counterPub.send = (jm: JobMessage) => Promise.resolve();
        const followPub: FollowersPublisher = mock.mock(FollowersPublisher);
        followPub.send = (jm: JobMessage) => Promise.resolve();
        const drain = new AMQPDrain(settings, counterPub, followPub);

        const msg: JobMessage = new JobMessage(
            "123", 1, {}, JSON.stringify({data: "test", settings: {}}),
        );

        return drain.open(msg)
            .then((result: boolean) => {
                assert.isTrue(result);
            });
    });
});
