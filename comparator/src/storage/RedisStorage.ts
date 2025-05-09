import Redis, { ChainableCommander } from 'ioredis';

export default class RedisStorage {

    public constructor(
        private readonly redis: Redis,
    ) {
    }

    public async lock(masterKey: string): Promise<void> {
        const lockKey = this.getLockKey(masterKey);
        const isLocked = await this.redis.get(lockKey);

        if (isLocked !== null) {
            throw new Error(`Master key ${masterKey} is already locked.`);
        }

        await this.redis.set(lockKey, '1', 'EX', 60 * 5);
    }

    public async unlock(masterKey: string): Promise<void> {
        await this.redis.del(this.getLockKey(masterKey));
    }

    public async getValues(masterKey: string, ids: string[]): Promise<(string|null)[]> {
        return this.redis.hmget(masterKey, ...ids);
    }

    public async getKeys(masterKey: string): Promise<string[]> {
        return this.redis.hkeys(masterKey);
    }

    public async getCount(masterKey: string): Promise<number> {
        return this.redis.hlen(masterKey);
    }

    public async delete(masterKey: string, externalId?: string): Promise<number> {
        if (externalId) {
            return this.redis.hdel(masterKey, externalId);
        }

        return this.redis.del(masterKey);
    }

    public hmSet(pipeline: ChainableCommander, masterKey: string, values: string[], ttl?: number): void {
        pipeline.hmset(masterKey, ...values);

        if (ttl) {
            pipeline.expire(masterKey, ttl);
        }
    }

    public getPipeline(): ChainableCommander {
        return this.redis.pipeline();
    }

    public getLockKey(masterKey: string): string {
        return `${masterKey}-lock`;
    }

    public getBufferKey(correlationId: string): string {
        return `${correlationId}-buffer`;
    }

}
