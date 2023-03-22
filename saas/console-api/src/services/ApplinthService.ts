import { IApplinthSearchQuery } from '../controllers/applinths';
import Applinth from '../entities/Applinth';
import { CollectionEnum } from '../enums/CollectionEnum';
import Mongo from '../storage/mongo/Mongo';
import BaseService from './BaseService';

export default class ApplinthService extends BaseService<Applinth, IApplinthSearchQuery> {

    public constructor(db: Mongo) {
        super(db.getCloudCollection(CollectionEnum.APPLINTH));
    }

    protected mapRecordToExport(support: Applinth): Applinth {
        return {
            _id: support._id,
            clientId: support.clientId ?? null,
            cloudId: support.cloudId ?? null,
            startDate: support.startDate ?? null,
            minPrice: support.minPrice ?? null,
            minPriceDate: support.minPriceDate ?? null,
        };
    }

}
