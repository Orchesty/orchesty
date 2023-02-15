import { Repository } from '@orchesty/nodejs-sdk/dist/lib/Storage/Mongo';
import { IndexDescription } from 'mongodb';
import { ComparatorLock } from '../../../model';

const TTL_MINUTES = 5;

export class ComparatorLockRepository extends Repository<ComparatorLock> {

    protected readonly indices: IndexDescription[] = [
        {
            name: 'ComparatorLock_ttl',
            key: { ttl: 1 },
            expireAfterSeconds: TTL_MINUTES * 60,
        },
    ];

    public async acquireLock(masterKey: string): Promise<boolean> {
        const now = new Date();
        const result = await this.collection.findOneAndUpdate(
            { masterKey },
            {
                $setOnInsert: {
                    masterKey,
                    ttl: now.setMinutes(now.getMinutes() + TTL_MINUTES),
                },
            },
            {
                upsert: true,
                returnDocument: 'before',
            },
        );

        return result.value === null;
    }

    public async unlock(masterKey: string): Promise<void> {
        return this.delete({ masterKey });
    }

}
