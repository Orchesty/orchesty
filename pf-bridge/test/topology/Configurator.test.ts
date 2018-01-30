import { assert } from "chai";
import "mocha";

import Configurator, {ITopologyConfig, ITopologyConfigSkeleton} from "../../src/topology/Configurator";

const testTopo: ITopologyConfigSkeleton = {
    id: "test-topo",
    topology_id: "test-topo",
    topology_name: "test-topo",
    nodes: [
        {
            id: "node_a",
            faucet: {
                type: "faucet.http",
                settings: {
                    port: 3333,
                },
            },
            next: ["node_b"],
        },
        {
            id: "node_b",
            label: {
                id: "node_b",
                node_id: "507f191e810c19729de860ea",
                node_name: "b",
                topology_id: "test-topo",
            },
            worker: { type: "worker.appender", settings: { suffix: "| something"} },
            next: [],
        },
    ],
};

const expectedTopo: ITopologyConfig = {
    counter: {
        pub: {
            exchange: {
                name: "pipes.test-topo.events",
                options: {},
                type: "direct",
            },
            queue: {
                name: "pipes.results",
                options: {},
            },
            routing_key: "process_finished",
        },
        sub: {
            queue: {
                name: "pipes.test-topo.counter",
                options: {},
                prefetch: 1,
            },
        },
    },
    id: "test-topo",
    topology_id: "test-topo",
    topology_name: "test-topo",
    nodes: [
        {
            debug: {
                host: "node_a",
                port: 8008,
                url: "http://node_a:8008/status",
            },
            drain: {
                settings: {
                    counter: {
                        queue: {
                            name: "pipes.test-topo.counter",
                            options: {},
                        },
                    },
                    repeater: {
                        queue: {
                            name: "pipes.repeater",
                            options: {},
                        },
                    },
                    faucet: {
                        queue: {
                            name: "pipes.test-topo.node_a",
                            options: {},
                        },
                    },
                    followers: [
                        {
                            exchange: {
                                name: "pipes.test-topo.events",
                                options: {},
                                type: "direct",
                            },
                            node_id: "node_b",
                            queue: {
                                name: "pipes.test-topo.node_b",
                                options: {},
                            },
                            routing_key: "test-topo.node_b",
                        },
                    ],
                    node_label: {
                        id: "node_a",
                        node_id: "node_a",
                        node_name: "node_a_unknown",
                        topology_id: "test-topo",
                    },
                },
                type: "drain.amqp",
            },
            faucet: {
                settings: {
                    node_label: {
                        id: "node_a",
                        node_id: "node_a",
                        node_name: "node_a_unknown",
                        topology_id: "test-topo",
                    },
                    port: 3333,
                },
                type: "faucet.http",
            },
            id: "node_a",
            initial: true,
            label: {
                id: "node_a",
                node_id: "node_a",
                node_name: "node_a_unknown",
                topology_id: "test-topo",
            },
            next: ["node_b"],
            worker: {
                settings: {
                    node_label: {
                        id: "node_a",
                        node_id: "node_a",
                        node_name: "node_a_unknown",
                        topology_id: "test-topo",
                    },
                },
                type: "worker.null",
            },
        },
        {
            debug: {
                host: "node_b",
                port: 8009,
                url: "http://node_b:8009/status",
            },
            drain: {
                settings: {
                    counter: {
                        queue: {
                            name: "pipes.test-topo.counter",
                            options: {},
                        },
                    },
                    repeater: {
                        queue: {
                            name: "pipes.repeater",
                            options: {},
                        },
                    },
                    faucet: {
                        queue: {
                            name: "pipes.test-topo.node_b",
                            options: {},
                        },
                    },
                    followers: [],
                    node_label: {
                        id: "node_b",
                        node_id: "507f191e810c19729de860ea",
                        node_name: "b",
                        topology_id: "test-topo",
                    },
                },
                type: "drain.amqp",
            },
            faucet: {
                settings: {
                    dead_letter_exchange: {
                        name: "pipes.dead-letter",
                        options: {},
                        type: "direct",
                    },
                    exchange: {
                        name: "pipes.test-topo.events",
                        options: {},
                        type: "direct",
                    },
                    node_label: {
                        id: "node_b",
                        node_id: "507f191e810c19729de860ea",
                        node_name: "b",
                        topology_id: "test-topo",
                    },
                    prefetch: 5,
                    queue: {
                        name: "pipes.test-topo.node_b",
                        options: {},
                    },
                    routing_key: "test-topo.node_b",
                },
                type: "faucet.amqp",
            },
            id: "node_b",
            initial: false,
            label: {
                id: "node_b",
                node_id: "507f191e810c19729de860ea",
                node_name: "b",
                topology_id: "test-topo",
            },
            next: [],
            worker: {
                settings: {
                    node_label: {
                        id: "node_b",
                        node_id: "507f191e810c19729de860ea",
                        node_name: "b",
                        topology_id: "test-topo",
                    },
                    suffix: "| something",
                },
                type: "worker.appender",
            },
        },
    ],
};

describe("Configurator", () => {
    it("should add defaults to missing topology skeleton fields when single topo", () => {
        const config: ITopologyConfig = Configurator.createConfigFromSkeleton(false, testTopo);
        assert.deepEqual(config, expectedTopo);
    });
    it("should not damage existing topology if converted multiple times", () => {
        const config: ITopologyConfig = Configurator.createConfigFromSkeleton(false, testTopo);
        const again: ITopologyConfig = Configurator.createConfigFromSkeleton(false, config);
        assert.deepEqual(again, expectedTopo);
    });
    it("should add defaults to missing topology skeleton fields when multi topo", () => {
        const config: ITopologyConfig = Configurator.createConfigFromSkeleton(true, testTopo);
        expectedTopo.counter.sub.queue.name = "pipes.multi-counter";
        expectedTopo.counter.pub.exchange.name = "pipes.events";
        expectedTopo.nodes[0].drain.settings.counter.queue.name = "pipes.multi-counter";
        expectedTopo.nodes[1].drain.settings.counter.queue.name = "pipes.multi-counter";
        assert.deepEqual(config, expectedTopo);
    });
});
