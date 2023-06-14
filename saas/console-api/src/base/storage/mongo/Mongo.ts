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
        await this.dropCollection(mongo.mongoBillingDbName, CollectionEnum.USAGE_STATS_HOURLY);
        await this.dropCollection(mongo.mongoBillingDbName, CollectionEnum.USAGE_STATS_DAILY);
        await this.dropCollection(mongo.mongoBillingDbName, CollectionEnum.USAGE_STATS_MONTHLY);
        await this.dropCollection(mongo.mongoBillingDbName, CollectionEnum.USAGE_STATS_METADATA);
        await this.dropCollection(mongo.mongoCloudDbName, CollectionEnum.TENANT);
        await this.dropCollection(mongo.mongoCloudDbName, CollectionEnum.ADDRESS);
        await this.dropCollection(mongo.mongoCloudDbName, CollectionEnum.APPLINTH);
        await this.dropCollection(mongo.mongoCloudDbName, CollectionEnum.CLIENT);
        await this.dropCollection(mongo.mongoCloudDbName, CollectionEnum.CLOUD);
        await this.dropCollection(mongo.mongoCloudDbName, CollectionEnum.CORRECTION);
        await this.dropCollection(mongo.mongoCloudDbName, CollectionEnum.MODULE);
        await this.dropCollection(mongo.mongoCloudDbName, CollectionEnum.ORCHESTY);
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
        await this.getCloudCollection(CollectionEnum.CLIENT).createIndexes([
            { key: { _id: 1 } },
            { key: { deleted: 1 } },
            { key: { invoicingId: 1 } },
            { key: { companyName: 1 } },
        ]);
        await this.getCloudCollection(CollectionEnum.CLOUD).createIndexes([
            { key: { _id: 1 } },
            { key: { deleted: 1 } },
            { key: { tenantId: 1 } },
        ]);
        await this.getCloudCollection(CollectionEnum.APPLINTH).createIndexes([
            { key: { _id: 1 } },
            { key: { deleted: 1 } },
            { key: { tenantId: 1 } },
            { key: { instanceId: 1 } },
        ]);
        await this.getCloudCollection(CollectionEnum.ORCHESTY).createIndexes([
            { key: { _id: 1 } },
            { key: { deleted: 1 } },
            { key: { tenantId: 1 } },
            { key: { instanceId: 1 } },
        ]);
        await this.getCloudCollection(CollectionEnum.CORRECTION).createIndexes([
            { key: { _id: 1 } },
            { key: { deleted: 1 } },
            { key: { tenantId: 1 } },
        ]);
        await this.getCloudCollection(CollectionEnum.MODULE).createIndexes([
            { key: { _id: 1 } },
            { key: { deleted: 1 } },
            { key: { appName: 1 } },
            { key: { applinthId: 1 } },
        ]);
        await this.getCloudCollection(CollectionEnum.MODULE).createIndex({
            appName: 1, applinthId: 1,
        }, { unique: true });
        await this.getCloudCollection(CollectionEnum.ADDRESS).createIndexes([
            { key: { _id: 1 } },
            { key: { deleted: 1 } },
            { key: { tenantId: 1 } },
        ]);
    }

    private async dropCollection(dbName: string, collection: string): Promise<void> {
        try {
            await this.client.db(dbName).dropCollection(collection);
        } catch (e) {
        }
    }

}
