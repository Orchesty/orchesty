import { assert } from "chai";
import "mocha";

import * as mongoose from "mongoose";
import { mongodbConnectionOptions as opts} from "../../src/config";
import TopologyStatusSwitch, {topologySchema} from "../../src/topology/TopologyStatusSwitch";
import { TOPOLOGY_STATUS } from "../../src/topology/TopologyStatusSwitch";

describe("TopologyStatusSwitch", () => {
    it.skip("will reject with error on invalid id", () => {
        const switcher = new TopologyStatusSwitch(opts);

        return switcher.setStatus("abcd", TOPOLOGY_STATUS.RUNNING)
            .catch((err) => {
                assert.include(
                    err,
                    "Error switching topology status. Error: CastError: Cast to ObjectId failed for value \"abcd\"",
                );
            });
    });

    it.skip("will switch statuses in mongo db", () => {
        const db = mongoose.createConnection(opts.host, opts.db, opts.port);
        const id = new mongoose.Types.ObjectId().toHexString();
        const model = db.model("Topology", topologySchema, opts.collection);

        return model.create({_id: id, status: TOPOLOGY_STATUS.NEW})
            .then(() => {
                const switcher = new TopologyStatusSwitch(opts);
                return switcher.setStatus(id, TOPOLOGY_STATUS.RUNNING);
            })
            .then((currentStatus: string) => {
                assert.equal(currentStatus, TOPOLOGY_STATUS.RUNNING);
                return model.findById(id);
            })
            .then((doc: any) => {
                assert.equal(doc.status, TOPOLOGY_STATUS.RUNNING);
            });
    });

});
