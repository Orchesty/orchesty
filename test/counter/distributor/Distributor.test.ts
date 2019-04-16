import { assert } from "chai";
import "mocha";
import Distributor, {ISyncObject} from "../../../src/counter/distributor/Distributor";
import CounterMessage from "../../../src/message/CounterMessage";
import Headers from "../../../src/message/Headers";
import {ResultCode} from "../../../src/message/ResultCode";
import {INodeLabel} from "../../../src/topology/Configurator";

const createSyncObj = (): ISyncObject => {
    let res;
    let rej;
    const label: INodeLabel = {
        id: "id",
        node_id: "node_id",
        node_name: "node_name",
        topology_id: "topology_id",
    };
    const headers = new Headers({
        "pf-topology-id": "topology_id",
        "pf-correlation-id": "correlation_id",
        "pf-process-id": "process_id",
        "pf-parent-id": "",
        "pf-sequence-id": "1",
        "pf-node-id": "test_node_4",
        "pf-node-name": "test_node_name_4",
    });
    const cm = new CounterMessage(label, headers.getRaw(), ResultCode.SUCCESS);
    const p = new Promise((resolve, reject) => {
        res = resolve;
        rej = reject;
    });

    return {
        msg: cm,
        resolve: res,
        reject: rej,
    };
};

describe("Distributor", () => {
    it("first should always return the oldest item #unit", async () => {
        const distributor = new Distributor();

        const item1 = createSyncObj();
        const item2 = createSyncObj();
        const item3 = createSyncObj();

        assert.isFalse(distributor.has("topology_id", "process_id"));
        assert.equal(distributor.length("topology_id", "process_id"), 0);

        distributor.add("topology_id", "process_id", item1);
        distributor.add("topology_id", "process_id", item2);
        distributor.add("topology_id", "process_id", item3);

        assert.isTrue(distributor.has("topology_id", "process_id"));
        assert.equal(distributor.length("topology_id", "process_id"), 3);

        assert.equal(distributor.first("topology_id", "process_id"), item1);
        distributor.shift("topology_id", "process_id");
        assert.equal(distributor.length("topology_id", "process_id"), 2);

        assert.equal(distributor.first("topology_id", "process_id"), item2);
        distributor.shift("topology_id", "process_id");
        assert.equal(distributor.length("topology_id", "process_id"), 1);

        assert.equal(distributor.first("topology_id", "process_id"), item3);
        distributor.shift("topology_id", "process_id");
        assert.equal(distributor.length("topology_id", "process_id"), 0);
    });
});
