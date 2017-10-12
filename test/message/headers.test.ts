import { assert } from "chai";
import "mocha";

import {default as Headers} from "../../src/message/Headers";

describe("Headers", () => {
    it("should return fail when some mandatory header is missing", () => {
        let h;
        assert.isFalse(Headers.containsAllMandatory(h));

        h = { pfp_correlation_id : "corrid" };
        assert.isFalse(Headers.containsAllMandatory(h));

        h = { pfp_correlation_id : "corrid", pfp_process_id: "procid" };
        assert.isFalse(Headers.containsAllMandatory(h));

        h = { pfp_correlation_id : "corrid", pfp_process_id: "procid", pfp_parent_id: "parid "};
        assert.isFalse(Headers.containsAllMandatory(h));

        h = { pfp_correlation_id : "corrid", pfp_process_id: "procid", pfp_parent_id: "parid ", pfp_sequence_id: 1};
        assert.isTrue(Headers.containsAllMandatory(h));

        h = { pfp_correlation_id : "corrid", pfp_process_id: "", pfp_parent_id: "parid ", pfp_sequence_id: 1};
        assert.isFalse(Headers.containsAllMandatory(h));
    });

    it("should filter headers and keep only those preficed", () => {
        const headers = {
            "content-type": "application/json",
            "content-length": 1024,
            "guid": "usertoken",
            "pfp_correlation_id": "corrid",
            "pf_guid": "pfusertoken",
        };

        assert.deepEqual(Headers.getPFHeaders(headers), {
            "content-type": "application/json",
            "pfp_correlation_id": "corrid",
            "pf_guid": "pfusertoken",
        });
    });
});
