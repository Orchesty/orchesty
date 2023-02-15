import { Repository } from '@orchesty/nodejs-sdk/dist/lib/Storage/Mongo';
import { IndexDescription } from 'mongodb';
import { ComparatorHash } from '../../../model';

export class ComparatorHashRepository extends Repository<ComparatorHash> {

    protected readonly indices: IndexDescription[] = [
        {
            name: 'ComparatorHash_ttl',
            key: { ttl: 1 },
            expireAfterSeconds: 0,
        },
    ];

    public async updateHash(externalId: string, hash: string, ttl?: Date): Promise<void> {
        await this.collection.updateOne(
            { externalId },
            {
                $set: { hash, ttl },
            },
        );
    }

}
