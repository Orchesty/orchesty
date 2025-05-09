import crypto from 'crypto';
import RedisStorage from '../../storage/RedisStorage';
import { IConfiguration, IInput, IOutput } from './types';

export const HASH_ALG = 'sha1';

export class Comparator {

    public constructor(
        private readonly redis: RedisStorage,
    ) {
    }

    public async compare(input: IInput, correlationId: string): Promise<IOutput> {
        const config = input.configuration;
        const output = this.getEmptyOutput();

        const ids = input.items.map((it) => String(it[config.idField]));
        if (!ids.length) {
            return output;
        }

        const hashes = await this.redis.getValues(config.masterKey, ids);
        const dataToStore: string[] = [];
        const bufferedData: string[] = [];

        input.items.forEach((it, index) => {
            const externalId = it[config.idField] as string;
            const hash = this.createHash(structuredClone(it), config.excludedFields);

            if (!hashes[index]) {
                this.prepareDataToStore(config, dataToStore, bufferedData, externalId, hash);
                output.created.push(it);

                return;
            }

            if (hashes[index] !== hash) {
                this.prepareDataToStore(config, dataToStore, bufferedData, externalId, hash);
                output.updated.push(it);
            }
        });

        const pipeline = this.redis.getPipeline();
        if (dataToStore.length > 0) {
            this.redis.hmSet(pipeline, config.masterKey, dataToStore, config.ttl);
        }

        if (bufferedData.length > 0) {
            const bufferKey = this.redis.getBufferKey(correlationId);
            this.redis.hmSet(pipeline, bufferKey, bufferedData, config.ttl ?? 3600);
        }

        if (dataToStore.length > 0 || bufferedData.length > 0) {
            await pipeline.exec();
        }

        return output;
    }

    public async getDeletedItems(config: IConfiguration, correlationId: string): Promise<string[]> {
        if (!config.totalCount && !config.isLast) {
            return [];
        }

        const bufferedKey = this.redis.getBufferKey(correlationId);
        if (config.totalCount) {
            const totalBuffered = await this.redis.getCount(bufferedKey);
            if (totalBuffered !== config.totalCount) {
                return [];
            }
        }

        const bufferedItems = await this.redis.getKeys(bufferedKey);
        const existingItems = await this.redis.getKeys(config.masterKey);
        const deletedItems = existingItems.filter((id) => !bufferedItems.includes(id));

        if (deletedItems.length > 0) {
            const pipeline = this.redis.getPipeline();
            pipeline.hdel(config.masterKey, ...deletedItems);
            await pipeline.exec();
        }

        return deletedItems;
    }

    public getEmptyOutput(): IOutput {
        return { created: [], updated: [], deleted: [] };
    }

    private createHash(data: object, excludedFields: string[] = []): string {
        const hasher = crypto.createHash(HASH_ALG);
        this.clearData(data, excludedFields);

        hasher.update(JSON.stringify(data));
        return hasher.digest('hex');
    }

    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    private clearData(data: any, excludedFields: string[]): void {
        excludedFields.forEach((path) => {
            const keys = path.split('.');
            const last = keys.length - 1;

            const local = keys.slice(0, last).reduce((acc, key) => acc?.[key] ?? null, data);

            if (local) {
                // eslint-disable-next-line @typescript-eslint/no-dynamic-delete
                delete local[keys[last]];
            }
        });
    }

    private prepareDataToStore(
        config: IConfiguration,
        dataToStore: string[],
        bufferedData: string[],
        externalId: string,
        hash: string,
    ): void {
        dataToStore.push(externalId, hash);

        if (config.deleted === true) {
            bufferedData.push(externalId, hash);
        }
    }

}
