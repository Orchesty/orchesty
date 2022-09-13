import { IndexDescription } from 'mongodb';
import { CollectionEnum } from '../../enums/CollectionEnum';
import Mongo from './Mongo';

export default class CloudMongo extends Mongo {

    protected readonly dbName = 'cloud';

    public async createIndexes(): Promise<void> {
        const specs: IndexDescription[] = [
            { key: { instanceId: 1 } },
            { key: { tenantId: 1 } },
        ];
        await this.getCollection(CollectionEnum.TENANT).createIndexes(specs);
    }

}
