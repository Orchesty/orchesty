/* tslint:disable:no-empty */

import { assert } from "chai";
import "mocha";

import * as rp from "request-promise";
import * as mock from "ts-mockito";
import AMQPDrain from "../../src/node/drain/AMQPDrain";
import HttpFaucet from "../../src/node/faucet/HttpFaucet";
import Node from "../../src/node/Node";
import UppercaseWorker from "../../src/node/worker/UppercaseWorker";

describe("Node", () => {
    it("prepare and start and open node", () => {
        const worker = mock.mock(UppercaseWorker);
        const drain = mock.mock(AMQPDrain);
        const faucet = mock.mock(HttpFaucet);
        const faucetInstance: HttpFaucet = mock.instance(faucet);
        faucetInstance.open = () => Promise.resolve();

        const node = new Node(
            "test-node",
            worker,
            faucetInstance,
            drain,
            5000,
            true,
        );

        return node.open()
            .then(() => {
                return node.startServer();
            })
            .then(() => {
                return rp("http://localhost:5000/status");
            })
            .then((resp: string) => {
                assert.equal("OK", resp);
            });
    });
});
