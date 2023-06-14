import { CollectionEnum } from '../../base/enums/CollectionEnum';
import Mongo from '../../base/storage/mongo/Mongo';
import { IModuleSearchQuery } from '../../controllers/modules';
import Module from '../entities/Module';
import BaseService from './BaseService';

export default class ModuleService extends BaseService<Module, IModuleSearchQuery> {

    public constructor(db: Mongo) {
        super(db.getCloudCollection(CollectionEnum.MODULE));
    }

    protected mapRecordToExport(module: Module): Module {
        return {
            ...super.mapRecordToExport(module),
            appName: module.appName,
            applinthId: module.applinthId,
            price: module.price,
            minPrice: module.minPrice,
            minPriceDate: module.minPriceDate,
        };
    }

    protected getEntityDateFields(): string[] {
        return [...super.getEntityDateFields(), 'minPriceDate'];
    }

}
