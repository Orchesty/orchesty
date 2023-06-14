import { ObjectId } from 'mongodb';
import { CollectionEnum } from '../../base/enums/CollectionEnum';
import Mongo from '../../base/storage/mongo/Mongo';
import { IApplinthSearchQuery } from '../../controllers/applinths';
import { ICloudSearchQuery } from '../../controllers/clouds';
import CloudController from '../cloudController/CloudController';
import Applinth from '../entities/Applinth';
import SearchError from '../errors/SearchError';
import BaseService from './BaseService';
import CloudService from './CloudService';

export default class ApplinthService extends BaseService<Applinth, IApplinthSearchQuery> {

    public constructor(
        db: Mongo,
        private readonly cloudService: CloudService,
        private readonly cloudController: CloudController,
    ) {
        super(db.getCloudCollection(CollectionEnum.APPLINTH));
    }

    public async createApplinth(query: IApplinthSearchQuery): Promise<Applinth> {
        const instance = await this.cloudController.create(query.instanceDisplayName ?? '');

        if (query.cloud !== undefined) {
            await this.cloudService.createCloud(query.cloud as ICloudSearchQuery);
        }

        const createQuery = {
            instanceId: instance,
            ...query,
        };
        return super.create(createQuery);
    }

    public async deleteApplinth(query: IApplinthSearchQuery): Promise<{ msg: string }> {
        await this.cloudController.remove(query.instanceDisplayName ?? '');

        let opplinth = null;
        try {
            opplinth = await this.collection.findOne({
                _id: new ObjectId(query._id),
                deleted: {
                    $eq: null,
                },
            }) as Applinth;
        } catch (e) {
            throw new SearchError((e as Error).message);
        }

        if (!opplinth) {
            throw new SearchError('Entity not found!');
        }

        const deleteQuery = {
            instanceId: opplinth.instanceId,
        };

        await this.cloudService.deleteCloud(deleteQuery as ICloudSearchQuery);

        return super.delete(deleteQuery as IApplinthSearchQuery);
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
