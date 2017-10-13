import { assert } from "chai";
import "mocha";

import {default as Headers} from "../../src/message/Headers";

describe("Headers", () => {
    it("should return fail when some mandatory header is missing", () => {
        let h;
        assert.isFalse(Headers.containsAllMandatory(h));

        h = { pf_correlation_id : "corrid" };
        assert.isFalse(Headers.containsAllMandatory(h));

        h = { pf_correlation_id : "corrid", pf_process_id: "procid" };
        assert.isFalse(Headers.containsAllMandatory(h));

        h = { pf_correlation_id : "corrid", pf_process_id: "procid", pf_parent_id: "parid "};
        assert.isFalse(Headers.containsAllMandatory(h));

        h = { pf_correlation_id : "corrid", pf_process_id: "procid", pf_parent_id: "parid ", pf_sequence_id: "1"};
        assert.isTrue(Headers.containsAllMandatory(h));

        h = { pf_correlation_id : "corrid", pf_process_id: "", pf_parent_id: "parid ", pf_sequence_id: "66"};
        assert.isFalse(Headers.containsAllMandatory(h));
    });

    it("should filter headers and keep only those prefixed", () => {
        const headers = {
            "content-type": "application/json",
            "content-length": 1024,
            "guid": "usertoken",
            "pf_correlation_id": "corrid",
            "pf_guid": "pfusertoken",
        };

        assert.deepEqual(Headers.getPFHeaders(headers), {
            "content-type": "application/json",
            "pf_correlation_id": "corrid",
            "pf_guid": "pfusertoken",
        });
    });
});
