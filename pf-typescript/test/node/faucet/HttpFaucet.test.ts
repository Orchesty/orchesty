import { assert } from "chai";
import "mocha";

import * as rp from "request-promise";
import JobMessage from "../../../src/message/JobMessage";
import HttpFaucet from "../../../src/node/faucet/HttpFaucet";

describe("HttpFaucet", () => {
    it("should handle http request", () => {
        const check = (msg: JobMessage) => {
            assert.equal(msg.getSequenceId(), 1);
            assert.equal(msg.getJobId(), "A23B23");
        };
        const faucet = new HttpFaucet({port: 6038});

        const workerFn = (msg: JobMessage) => {
            check(msg);
            return Promise.resolve(msg);
        };

        const drainFn = (msg: JobMessage) => {
            check(msg);
            return Promise.resolve(true);
        };

        return faucet.open(workerFn, drainFn)
            .then(() => {
                const options = {
                    method: "post",
                    uri: "http://localhost:6038/",
                    headers: {
                        job_id: "A23B23",
                        sequence_id: 1,
                    },
                };

                return rp(options);
            })
            .then((resp: string) => {
                assert.equal(resp, "OK");
            });
    });

    it("should respond with 500 error on missing headers", () => {
        const faucet = new HttpFaucet({port: 6039});

        const workerFn = (msg: JobMessage) => {
            return Promise.resolve(msg);
        };

        const drainFn = (msg: JobMessage) => {
            return Promise.resolve(true);
        };

        return faucet.open(workerFn, drainFn)
            .then(() => {
                const options = {
                    method: "post",
                    uri: "http://localhost:6039/",
                    headers: {
                        // Missing job_id header
                        // job_id: "A23B23",
                        sequence_id: 1,
                    },
                };

                return rp(options);
            })
            .catch((err: any) => {
                assert.equal(500, err.statusCode);
                assert.include(err.message, "Cannot create JobMessage from http request.");
            });
    });
});
