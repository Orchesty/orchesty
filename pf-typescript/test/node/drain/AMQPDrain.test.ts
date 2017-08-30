import { assert } from "chai";
import "mocha";

import * as mock from "ts-mockito";
import JobMessage from "../../../src/message/JobMessage";
import CounterPublisher from "../../../src/node/drain/amqp/CounterPublisher";
import FollowersPublisher from "../../../src/node/drain/amqp/FollowersPublisher";
import AMQPDrain, {IAMQPDrainSettings} from "../../../src/node/drain/AMQPDrain";

const settings: IAMQPDrainSettings = {
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
            routing_key: "amqpdrain-RK",
        },
    ],
};

describe("AMQPDrain", () => {
    it("forward to counter and followers on open", () => {
        const counterMock: CounterPublisher = mock.mock(CounterPublisher);
        // mock.when(counterMock.send).thenReturn((jm: JobMessage) => Promise.resolve());
        // const counterPublisher: CounterPublisher = mock.instance(counterMock);
        counterMock.send = (jm: JobMessage) => Promise.resolve();

        const followMock: FollowersPublisher = mock.mock(FollowersPublisher);
        // mock.when(followMock.send).thenReturn((jm: JobMessage) => Promise.resolve());
        // const followerPublisher: FollowersPublisher = mock.instance(followMock);
        followMock.send = (jm: JobMessage) => Promise.resolve();

        // const drain = new AMQPDrain(settings, counterPublisher, followerPublisher);
        const drain = new AMQPDrain(settings, counterMock, followMock);

        const msg: JobMessage = new JobMessage(
            { job_id: "123", sequence_id: 1}, JSON.stringify({data: "test", settings: {}}),
        );

        return drain.open(msg);
    });
});
