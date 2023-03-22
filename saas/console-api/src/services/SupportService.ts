import { ISupportSearchQuery } from '../controllers/supports';
import Support from '../entities/Support';
import { CollectionEnum } from '../enums/CollectionEnum';
import Mongo from '../storage/mongo/Mongo';
import BaseService from './BaseService';

export default class SupportService extends BaseService<Support, ISupportSearchQuery> {

    public constructor(db: Mongo) {
        super(db.getCloudCollection(CollectionEnum.SUPPORT));
    }

    protected mapRecordToExport(support: Support): Support {
        return {
            _id: support._id,
            clientId: support.clientId ?? null,
            hourlyRate: support.hourlyRate ?? null,
            subscription: support.subscription ?? null,
            responseTime: support.responseTime ?? null,
        };
    }

}
