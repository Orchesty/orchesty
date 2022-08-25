import { Collection, IndexDescription, MongoClient } from 'mongodb';
import { CollectionEnum } from '../../enums/CollectionEnum';

export default class Mongo {

    private readonly client: MongoClient;

    public constructor(dsn: string) {
        this.client = new MongoClient(dsn);
    }

    public async connect(): Promise<MongoClient> {
        return this.client.connect();
    }

    public async disconnect(): Promise<void> {
        return this.client.close();
    }

    public getCollection(collection: string): Collection {
        return this.client.db()
            .collection(collection);
    }

    public async createIndexes(): Promise<void> {
        const specs: IndexDescription[] = [
            { key: { start: 1 } },
            { key: { end: 1 } },
            { key: { tenantId: 1 } },
            { key: { endUserId: 1 } },
            { key: { endUserDisplayId: 1 } },
            { key: { appName: 1 } },
        ];
        await this.getCollection(CollectionEnum.USAGE_STATS_HOURLY).createIndexes(specs);
        await this.getCollection(CollectionEnum.USAGE_STATS_DAILY).createIndexes(specs);
        await this.getCollection(CollectionEnum.USAGE_STATS_MONTHLY).createIndexes(specs);
    }

}
