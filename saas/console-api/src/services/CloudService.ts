import { ICloudSearchQuery } from '../controllers/clouds';
import Cloud from '../entities/Cloud';
import { CollectionEnum } from '../enums/CollectionEnum';
import Mongo from '../storage/mongo/Mongo';
import BaseService from './BaseService';

export default class CloudService extends BaseService<Cloud, ICloudSearchQuery> {

    public constructor(db: Mongo) {
        super(db.getCloudCollection(CollectionEnum.CLOUD));
    }

    protected mapRecordToExport(cloud: Cloud): Cloud {
        return {
            ...super.mapRecordToExport(cloud),
            tenantId: cloud.tenantId,
            plan: cloud.plan,
            price: cloud.price,
            period: cloud.period,
            startDate: cloud.startDate,
            closeDate: cloud.closeDate ?? null,
        };
    }

    protected getEntityDateFields(): string[] {
        return [...super.getEntityDateFields(), 'startDate', 'closeDate'];
    }

}
