import { container } from '@orchesty/nodejs-sdk';
import NodeTester from '@orchesty/nodejs-sdk/dist/test/Testers/NodeTester';
import { NAME as COMPARATOR_INVALIDATE } from '../../src/custom_node/ComparatorInvalidate';
import RedisStorage from '../../src/storage/RedisStorage';

let tester: NodeTester;
let redisStorage: RedisStorage;

const MASTER_KEY = 'invalidateKey';

describe('Tests for ComparatorInvalidate', () => {
    beforeAll(() => {
        tester = new NodeTester(container, __filename);
        redisStorage = container.get(RedisStorage);
    });

    beforeEach(async () => {
        const pipeline = redisStorage.getPipeline();
        const dataToStore = ['id', 'hash1', 'id2', 'hash2'];
        redisStorage.hmSet(pipeline, MASTER_KEY, dataToStore);
        await pipeline.exec();
    });

    it('process - ok', async () => {
        await tester.testCustomNode(COMPARATOR_INVALIDATE);

        const count = await redisStorage.getCount(MASTER_KEY);

        expect(count).toEqual(0);
    });

    it('process - externalid', async () => {
        await tester.testCustomNode(COMPARATOR_INVALIDATE, 'externalid');

        const count = await redisStorage.getCount(MASTER_KEY);

        expect(count).toEqual(1);
    });
});
