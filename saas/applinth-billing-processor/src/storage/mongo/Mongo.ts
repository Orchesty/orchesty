import { Collection, MongoClient } from 'mongodb';
import { config } from '../../config';

export enum CollectionEnum {
    USAGE_STATS_MONTHLY = 'usage_stats_monthly',
    USAGE_STATS_METADATA = 'usage_stats_metadata',
    APPLINTH = 'applinth',
    MODULE = 'module',
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

}
