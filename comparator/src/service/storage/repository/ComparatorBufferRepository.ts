import { AbstractRepository, MongoDb } from '@orchesty/nodejs-sdk/dist/lib/Storage/Mongo';
import { IndexDescription } from 'mongodb';
import { ComparatorBuffer } from '../../../model';
import { Comparator } from '../../comparator';

export interface IBufferInfo {
    closed: boolean;
    total: number;
}

export class ComparatorBufferRepository extends AbstractRepository<ComparatorBuffer> {

    protected readonly indices: IndexDescription[] = [
        {
            name: 'ComparatorBuffer_ttl',
            key: { ttl: 1 },
            expireAfterSeconds: 60 * 60,
        },
    ];

    public constructor(private readonly comparator: Comparator, client: MongoDb, collectionName: string) {
        super(client, collectionName);
    }

    public async upsertBuffer(buffer: ComparatorBuffer): Promise<IBufferInfo> {
        const updateFilter: Record<string, Record<string, unknown>> = {
            $set: {
                ttl: new Date(),
                key: buffer.key,
            },
        };
        if (buffer.closed) {
            updateFilter.$set.closed = buffer.closed;
        }

        const dataHash = this.comparator.createHash(buffer.data, []);
        if (await this.isUniquePage(buffer.key, dataHash)) {
            updateFilter.$push = {
                data: { $each: buffer.data },
                pages: dataHash,
            };
        }

        const result = await this.collection.findOneAndUpdate(
            { key: buffer.key },
            updateFilter,
            {
                returnDocument: 'after',
                upsert: true,
                projection: {
                    closed: 1,
                    total: { $size: '$data' },
                },
            },
        );

        return result.value as unknown as IBufferInfo;
    }

    /**
     * In case of Repeats to ensure, that single page is not stored twice
     */
    private async isUniquePage(key: string, page: string): Promise<boolean> {
        const result = await this.collection.findOne({ key, pages: page });

        return result === null;
    }

}
