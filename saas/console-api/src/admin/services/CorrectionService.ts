import { CollectionEnum } from '../../base/enums/CollectionEnum';
import Mongo from '../../base/storage/mongo/Mongo';
import { ICorrectionSearchQuery } from '../../controllers/corrections';
import Correction from '../entities/Correction';
import BaseService from './BaseService';

export default class CorrectionService extends BaseService<Correction, ICorrectionSearchQuery> {

    public constructor(db: Mongo) {
        super(db.getCloudCollection(CollectionEnum.CORRECTION));
    }

    protected mapRecordToExport(correction: Correction): Correction {
        return {
            ...super.mapRecordToExport(correction),
            tenantId: correction.tenantId,
            date: correction.date,
            hours: correction.hours,
            amount: correction.amount,
            note: correction.note,
        };
    }

    protected getEntityDateFields(): string[] {
        return [...super.getEntityDateFields(), 'date'];
    }

}
