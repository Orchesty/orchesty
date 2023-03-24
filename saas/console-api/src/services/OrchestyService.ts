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
            ...super.mapRecordToExport(orchesty),
            tenantId: orchesty.tenantId,
            instanceId: orchesty.instanceId,
            version: orchesty.version,
            price: orchesty.price,
            startDate: orchesty.startDate,
        };
    }

    protected getEntityDateFields(): string[] {
        return [...super.getEntityDateFields(), 'startDate'];
    }

}
