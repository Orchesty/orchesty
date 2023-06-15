import { Collection, MongoClient } from 'mongodb';
import { config } from '../../config';

export enum CollectionEnum {
    USAGE_STATS_MONTHLY = 'usage_stats_monthly',
    USAGE_STATS_METADATA = 'usage_stats_metadata',
    APPLINTH = 'applinth',
    MODULE = 'module',
    CLOUD = 'cloud',
    ORCHESTY = 'orchesty',
    EVENTS = 'Events',
}

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

    public getBillingCollection(collection: CollectionEnum): Collection {
        return this.client.db(config.billingDb).collection(collection);
    }

    public getBillingAdminCollection(collection: CollectionEnum): Collection {
        return this.client.db(config.billingAdminDb).collection(collection);
    }

    public getUsageStatsCollection(collection: CollectionEnum): Collection {
        return this.client.db(config.usDb).collection(collection);
    }

    // Collections 'usage_stats_monthly', 'usage_stats_metadata', 'applinth' and 'module' have indexes in Mongo class in console-api
    public async createUsageStatsIndexes(): Promise<void> {
        await this.getUsageStatsCollection(CollectionEnum.EVENTS).createIndexes([
            { key: { created: 1 } },
            { key: { data: 1 } },
            { key: { iid: 1 } },
        ]);
    }

    public async dropCollections(): Promise<void> {
        await this.dropCollection(config.billingDb, CollectionEnum.USAGE_STATS_MONTHLY);
        await this.dropCollection(config.billingDb, CollectionEnum.USAGE_STATS_METADATA);
        await this.dropCollection(config.usDb, CollectionEnum.EVENTS);
        await this.dropCollection(config.billingAdminDb, CollectionEnum.APPLINTH);
        await this.dropCollection(config.billingAdminDb, CollectionEnum.MODULE);
        await this.dropCollection(config.billingAdminDb, CollectionEnum.ORCHESTY);
        await this.dropCollection(config.billingAdminDb, CollectionEnum.CLOUD);
    }

    private async dropCollection(dbName: string, collection: string): Promise<void> {
        try {
            await this.client.db(dbName).dropCollection(collection);
        } catch (e) {
        }
    }

}
