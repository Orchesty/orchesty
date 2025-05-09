import { container } from '@orchesty/nodejs-sdk';
import Redis from 'ioredis';
import { REDIS_SERVICE_NAME } from '../../../src';
import RedisStorage from '../../../src/storage/RedisStorage';

let redisStorage: RedisStorage;
let redis: Redis;

async function insertData(key: string): Promise<void> {
    const pipeline = redisStorage.getPipeline();
    const dataToStore = ['id1', 'hash1', 'id2', 'hash2', 'id3', 'hash3'];
    redisStorage.hmSet(pipeline, key, dataToStore);
    await pipeline.exec();
}

describe('RedisStorage', () => {
    beforeAll(() => {
        redis = container.getNamed(REDIS_SERVICE_NAME);
        redisStorage = container.get(RedisStorage);
    });

    it('lock', async () => {
        await redisStorage.lock('test');
        const isLocked = await redis.get('test-lock');

        expect(isLocked).toBe('1');
    });

    it('lock - already locked', async () => {
        await redisStorage.lock('test');

        try {
            await redisStorage.lock('test');
        } catch (err: unknown) {
            expect((err as Error).message).toBe('Master key test is already locked.');
        }
    });

    it('unlock', async () => {
        await redisStorage.lock('test');
        await redisStorage.unlock('test');

        const isLocked = await redis.get('test-lock');

        expect(isLocked).toBe(null);
    });

    it('getValues', async () => {
        await insertData('testVal');
        const res = await redisStorage.getValues('testVal', ['id1', 'id_non_exists', 'id3', 'id4']);

        expect(res).toStrictEqual(
            ['hash1', null, 'hash3', null],
        );
    });

    it('getKeys', async () => {
        await insertData('testKey');
        const res = await redisStorage.getKeys('testKey');

        expect(res).toStrictEqual(
            ['id1', 'id2', 'id3'],
        );
    });

    it('getCount', async () => {
        await insertData('testCount');
        const res = await redisStorage.getCount('testCount');

        expect(res).toBe(3);
    });

    it('delete - masterKey', async () => {
        const key = 'testDeleteMaster';
        await insertData(key);
        await redisStorage.delete(key);

        const exists = await redis.get(key);

        expect(exists).toBe(null);
    });

    it('delete - externalId', async () => {
        const key = 'testDeleteExternalId';
        await insertData(key);
        await redisStorage.delete(key, 'id2');

        const count = await redisStorage.getCount(key);

        expect(count).toBe(2);
    });
});
