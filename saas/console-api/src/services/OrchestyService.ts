import { IOrchestySearchQuery } from '../controllers/orchestras';
import Orchesty from '../entities/Orchesty';
import { CollectionEnum } from '../enums/CollectionEnum';
import BillingMongo from '../storage/mongo/Mongo';
import BaseService from './BaseService';

export default class OrchestyService extends BaseService<Orchesty, IOrchestySearchQuery> {

    public constructor(db: BillingMongo) {
        super(db.getCloudCollection(CollectionEnum.ORCHESTY));
    }

    protected mapRecordToExport(orchesty: Orchesty): Orchesty {
        return {
            _id: orchesty._id,
            clientId: orchesty.clientId ?? null,
            cloudId: orchesty.cloudId ?? null,
            version: orchesty.version ?? null,
            price: orchesty.price ?? null,
            startDate: orchesty.startDate ?? null,
        };
    }

}
