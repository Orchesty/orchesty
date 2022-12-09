import { Collection, IndexDescription, MongoClient } from 'mongodb';
import { mongo } from '../../config/config';
import { CollectionEnum } from '../../enums/CollectionEnum';

export default class Mongo {

    protected readonly client: MongoClient;

    public constructor(dsn: string) {
        this.client = new MongoClient(dsn);
    }

    public async connect(): Promise<MongoClient> {
        return this.client.connect();
    }

    public async disconnect(): Promise<void> {
        return this.client.close();
    }

    public getBillingCollection(collection: string): Collection {
        return this.client.db(mongo.mongoBillingDbName).collection(collection);
    }

    public getCloudCollection(collection: string): Collection {
        return this.client.db(mongo.mongoCloudDbName).collection(collection);
    }

    public async dropCollections(): Promise<void> {
        await this.client.db(mongo.mongoCloudDbName).dropCollection(CollectionEnum.TENANT);
        await this.client.db(mongo.mongoBillingDbName).dropCollection(CollectionEnum.USAGE_STATS_HOURLY);
        await this.client.db(mongo.mongoBillingDbName).dropCollection(CollectionEnum.USAGE_STATS_DAILY);
        await this.client.db(mongo.mongoBillingDbName).dropCollection(CollectionEnum.USAGE_STATS_MONTHLY);
        await this.client.db(mongo.mongoBillingDbName).dropCollection(CollectionEnum.USAGE_STATS_METADATA);
    }

    public async createBillingIndexes(): Promise<void> {
        const specs: IndexDescription[] = [
            { key: { start: 1 } },
            { key: { end: 1 } },
            { key: { tenantId: 1 } },
            { key: { endUserId: 1 } },
            { key: { endUserDisplayId: 1 } },
            { key: { appName: 1 } },
        ];
        const metadataSpecs: IndexDescription[] = [
            { key: { tenantId: 1 } },
            { key: { instances: 1 } },
        ];
        await this.getBillingCollection(CollectionEnum.USAGE_STATS_HOURLY).createIndexes(specs);
        await this.getBillingCollection(CollectionEnum.USAGE_STATS_DAILY).createIndexes(specs);
        await this.getBillingCollection(CollectionEnum.USAGE_STATS_MONTHLY).createIndexes(specs);
        await this.getBillingCollection(CollectionEnum.USAGE_STATS_METADATA).createIndexes(metadataSpecs);
    }

    public async createCloudIndexes(): Promise<void> {
        const specs: IndexDescription[] = [
            { key: { instanceId: 1 } },
            { key: { tenantId: 1 } },
        ];
        await this.getCloudCollection(CollectionEnum.TENANT).createIndexes(specs);
    }

}
