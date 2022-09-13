import { IndexDescription } from 'mongodb';
import { CollectionEnum } from '../../enums/CollectionEnum';
import Mongo from './Mongo';

export default class BillingMongo extends Mongo {

    protected readonly dbName = 'billing';

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
