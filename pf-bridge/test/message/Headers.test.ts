import { assert } from "chai";
import "mocha";

import {default as Headers} from "../../src/message/Headers";

describe("Headers", () => {
    it("containsAllMandatory should return false when some mandatory header is missing", () => {
        let h;
        assert.isFalse(Headers.containsAllMandatory(h));

        h = { "pf-correlation-id" : "corrid" };
        assert.isFalse(Headers.containsAllMandatory(h));

        h = { "pf-correlation-id" : "corrid", "pf-process-id": "procid" };
        assert.isFalse(Headers.containsAllMandatory(h));

        h = { "pf-correlation-id" : "corrid", "pf-process-id": "procid", "pf-parent-id": "par"};
        assert.isFalse(Headers.containsAllMandatory(h));

        h = { "pf-correlation-id" : "corrid", "pf-process-id": "procid", "pf-parent-id": "par", "pf-sequence-id": "1"};
        assert.isTrue(Headers.containsAllMandatory(h));

        h = { "pf-correlation-id" : "corrid", "pf-process-id": "", "pf-parent-id": "par", "pf-sequence-id": "66"};
        assert.isFalse(Headers.containsAllMandatory(h));
    });

    it("should filter headers and keep only those prefixed", () => {
        const headers = {
            "content-type": "application/json",
            "content-length": 1024,
            "guid": "usertoken",
            "pf-correlation-id": "corrid",
            "pf-guid": "pfusertoken",
        };

        assert.deepEqual(Headers.getPFHeaders(headers), {
            "content-type": "application/json",
            "pf-correlation-id": "corrid",
            "pf-guid": "pfusertoken",
        });
    });
});
