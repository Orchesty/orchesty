import { ICloudSearchQuery } from '../controllers/clouds';
import Cloud from '../entities/Cloud';
import { CollectionEnum } from '../enums/CollectionEnum';
import BillingMongo from '../storage/mongo/Mongo';
import BaseService from './BaseService';

export default class CloudService extends BaseService<Cloud, ICloudSearchQuery> {

    public constructor(db: BillingMongo) {
        super(db.getCloudCollection(CollectionEnum.CLOUD));
    }

    protected mapRecordToExport(support: Cloud): Cloud {
        return {
            _id: support._id,
            clientId: support.clientId ?? null,
            plan: support.plan ?? null,
            price: support.price ?? null,
            period: support.period ?? null,
            startDate: support.startDate ?? null,
            closeDate: support.closeDate ?? null,
        };
    }

}
