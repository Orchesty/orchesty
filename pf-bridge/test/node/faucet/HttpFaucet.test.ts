import { assert } from "chai";
import "mocha";

import * as rp from "request-promise";
import JobMessage from "../../../src/message/JobMessage";
import HttpFaucet from "../../../src/node/faucet/HttpFaucet";
import {FaucetProcessMsgFn} from "../../../src/node/faucet/IFaucet";

describe("HttpFaucet", () => {
    it("should handle http request", () => {
        const check = (msg: JobMessage) => {
            assert.equal(msg.getSequenceId(), 1);
            assert.equal(msg.getJobId(), "A23B23");
        };
        const faucet = new HttpFaucet({port: 6038, node_id: "someId"});

        const processFn: FaucetProcessMsgFn = (msg: JobMessage) => {
            check(msg);
            return Promise.resolve(msg);
        };

        return faucet.open(processFn)
            .then(() => {
                const options = {
                    method: "post",
                    uri: "http://localhost:6038/",
                    headers: {
                        correlation_id: "corrid",
                        process_id: "A23B23",
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
        const faucet = new HttpFaucet({port: 6039, node_id: "someId"});

        const processFn: FaucetProcessMsgFn = (msg: JobMessage) => {
            return Promise.resolve(msg);
        };

        return faucet.open(processFn)
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
