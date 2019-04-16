import { assert } from "chai";
import "mocha";

import ILimiter from "../../../src/limiter/ILimiter";
import Headers from "../../../src/message/Headers";
import JobMessage from "../../../src/message/JobMessage";
import IWorker from "../../../src/node/worker/IWorker";
import LimiterWorker from "../../../src/node/worker/LimiterWorker";

describe("LimiterWorker", () => {

    it("isWorkerReady should return result according to the limiter and inner worker readiness #unit", async () => {
        const limiterMock: ILimiter = {
            isReady: async () => false,
            canBeProcessed: async () => true,
            postpone: async () => null,
        };

        const workerMock: IWorker = {
            isWorkerReady: async () => false,
            processData: async () => null,
            processService: async () => null,
        };

        const faucetConf: any = { settings: { exchange: {name: "exname"}, routing_key: "rk"}};
        const readyWorker = new LimiterWorker(limiterMock, workerMock, faucetConf);

        assert.isFalse(await readyWorker.isWorkerReady());

        limiterMock.isReady = async () => true;
        assert.isFalse(await readyWorker.isWorkerReady());

        workerMock.isWorkerReady = async () => true;
        assert.isTrue(await readyWorker.isWorkerReady());

        limiterMock.isReady = async () => false;
        assert.isFalse(await readyWorker.isWorkerReady());
    });

    it("processData should call limiter's postpone if cannot be processed #unit", async () => {
        const hdrs = new Headers();
        hdrs.setPFHeader(Headers.CORRELATION_ID, "1");
        hdrs.setPFHeader(Headers.PROCESS_ID, "1");
        hdrs.setPFHeader(Headers.SEQUENCE_ID, "1");
        hdrs.setPFHeader(Headers.PARENT_ID, "1");
        const msg = new JobMessage(
            {id: "1", node_id: "1", node_name: "1", topology_id: "t"},
            hdrs.getRaw(),
            Buffer.from(""),
        );

        return new Promise(async (resolve) => {
            const limiterMock: ILimiter = {
                isReady: async () => true,
                canBeProcessed: async () => false,
                postpone: async (m: JobMessage) => {
                    assert.equal(m, msg, "Postponed message should be the same instance of original message");
                },
            };

            const workerMock: IWorker = {
                isWorkerReady: async () => true,
                processData: async () => null,
                processService: async () => null,
            };

            const faucetConf: any = { settings: { exchange: {name: "exname"}, routing_key: "rk"}};
            const limWorker = new LimiterWorker(limiterMock, workerMock, faucetConf);
            const out = await limWorker.processData(msg);
            assert.equal(out.length, 0);
            resolve();
        });
    });

    it("processData should call limiter's postpone and do not fail if it throws error #unit", async () => {
        const hdrs = new Headers();
        hdrs.setPFHeader(Headers.CORRELATION_ID, "1");
        hdrs.setPFHeader(Headers.PROCESS_ID, "1");
        hdrs.setPFHeader(Headers.SEQUENCE_ID, "1");
        hdrs.setPFHeader(Headers.PARENT_ID, "1");
        const msg = new JobMessage(
            {id: "1", node_id: "1", node_name: "1", topology_id: "t"},
            hdrs.getRaw(),
            Buffer.from(""),
        );

        return new Promise(async (resolve) => {
            const limiterMock: ILimiter = {
                isReady: async () => true,
                canBeProcessed: async () => false,
                postpone: async (m: JobMessage) => {
                    assert.equal(m, msg, "Postponed message should be the same instance of original message");
                    throw new Error("some error");
                },
            };

            const workerMock: IWorker = {
                isWorkerReady: async () => true,
                processData: async () => null,
                processService: async () => null,
            };

            const faucetConf: any = { settings: { exchange: {name: "exname"}, routing_key: "rk"}};
            const limWorker = new LimiterWorker(limiterMock, workerMock, faucetConf);
            const out = await limWorker.processData(msg);
            assert.equal(out.length, 0);
            resolve();
        });
    });

});
