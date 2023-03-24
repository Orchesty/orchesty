import { IApplinthSearchQuery } from '../controllers/applinths';
import Applinth from '../entities/Applinth';
import { CollectionEnum } from '../enums/CollectionEnum';
import Mongo from '../storage/mongo/Mongo';
import BaseService from './BaseService';

export default class ApplinthService extends BaseService<Applinth, IApplinthSearchQuery> {

    public constructor(db: Mongo) {
        super(db.getCloudCollection(CollectionEnum.APPLINTH));
    }

    protected mapRecordToExport(applinth: Applinth): Applinth {
        return {
            ...super.mapRecordToExport(applinth),
            tenantId: applinth.tenantId,
            instanceId: applinth.instanceId,
            minPrice: applinth.minPrice,
            minPriceDate: applinth.minPriceDate,
        };
    }

    protected getEntityDateFields(): string[] {
        return [...super.getEntityDateFields(), 'minPriceDate'];
    }

}
