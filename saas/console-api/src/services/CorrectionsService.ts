import { ICorrectionSearchQuery } from '../controllers/corrections';
import Correction from '../entities/Correction';
import { CollectionEnum } from '../enums/CollectionEnum';
import Mongo from '../storage/mongo/Mongo';
import BaseService from './BaseService';

export default class CorrectionsService extends BaseService<Correction, ICorrectionSearchQuery> {

    public constructor(db: Mongo) {
        super(db.getCloudCollection(CollectionEnum.CORRECTION));
    }

    protected mapRecordToExport(correction: Correction): Correction {
        return {
            _id: correction._id,
            clientId: correction.clientId ?? null,
            date: correction.date ?? null,
            hours: correction.hours ?? null,
            amount: correction.amount ?? null,
            note: correction.note ?? null,
        };
    }

}
