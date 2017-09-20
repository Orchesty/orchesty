/* tslint:disable:no-empty */

import { assert } from "chai";
import "mocha";

import * as rp from "request-promise";
import * as mock from "ts-mockito";
import AmqpDrain from "../../src/node/drain/AmqpDrain";
import HttpFaucet from "../../src/node/faucet/HttpFaucet";
import Node from "../../src/node/Node";
import UppercaseWorker from "../../src/node/worker/UppercaseWorker";

describe("Node", () => {
    it("prepare and start and open node, when worker is ready", () => {
        const worker = mock.mock(UppercaseWorker);
        worker.isWorkerReady = () => Promise.resolve(true);
        const drain = mock.mock(AmqpDrain);
        const faucet = mock.mock(HttpFaucet);
        const faucetInstance: HttpFaucet = mock.instance(faucet);
        faucetInstance.open = () => Promise.resolve();

        const node = new Node(
            "test-node",
            worker,
            faucetInstance,
            drain,
            5002,
            true,
        );

        return node.open()
            .then(() => {
                return node.startServer();
            })
            .then(() => {
                return rp("http://localhost:5002/status");
            })
            .then((resp: string) => {
                assert.equal(resp, "Bridge and worker are both ready.");
            });
    });
    it("prepare and start and open node, when worker is not ready yet", () => {
        const worker = mock.mock(UppercaseWorker);
        worker.isWorkerReady = () => Promise.resolve(false);
        const drain = mock.mock(AmqpDrain);
        const faucet = mock.mock(HttpFaucet);
        const faucetInstance: HttpFaucet = mock.instance(faucet);
        faucetInstance.open = () => Promise.resolve();

        const node = new Node(
            "test-node",
            worker,
            faucetInstance,
            drain,
            5001,
            true,
        );

        return node.open()
            .then(() => {
                return node.startServer();
            })
            .then(() => {
                return rp("http://localhost:5001/status");
            })
            .catch((err: any) => {
                assert.equal(err.statusCode, 503);
                assert.equal(err.error, "Worker not ready yet");
            });
    });
});
