import { assert } from "chai";
import "mocha";

import {Measurement} from "../../src/message/Measurement";

describe("Measurement", () => {
    it("should count waiting duration properly #unit", () => {
        const now = Date.now();

        const cases = [
            {pub: now - 5, rec: now, expected: 5},
            {pub: now + 5, rec: now, expected: 0},
            {rec: now, expected: 0},
        ];

        cases.forEach((testCase: any) => {
            const m = new Measurement();
            m.setPublished(testCase.pub);
            m.setReceived(testCase.rec);

            assert.equal(m.getWaitingDuration(), testCase.expected);
        });
    });
});
