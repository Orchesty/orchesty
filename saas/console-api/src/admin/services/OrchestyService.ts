import { ObjectId } from 'mongodb';
import { CollectionEnum } from '../../base/enums/CollectionEnum';
import Mongo from '../../base/storage/mongo/Mongo';
import { ICloudSearchQuery } from '../../controllers/clouds';
import { IOrchestySearchQuery } from '../../controllers/orchestras';
import CloudController from '../cloudController/CloudController';
import Orchesty from '../entities/Orchesty';
import SearchError from '../errors/SearchError';
import BaseService from './BaseService';
import CloudService from './CloudService';

export default class OrchestyService extends BaseService<Orchesty, IOrchestySearchQuery> {

    public constructor(
        db: Mongo,
        private readonly cloudService: CloudService,
        private readonly cloudController: CloudController,
    ) {
        super(db.getCloudCollection(CollectionEnum.ORCHESTY));
    }

    public async createOrchesty(query: IOrchestySearchQuery): Promise<Orchesty> {
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

    public async deleteOrchesty(query: IOrchestySearchQuery): Promise<{ msg: string }> {
        await this.cloudController.remove(query.instanceDisplayName ?? '');

        let orchesty = null;
        try {
            orchesty = await this.collection.findOne({
                _id: new ObjectId(query._id),
                deleted: {
                    $eq: null,
                },
            }) as Orchesty;
        } catch (e) {
            throw new SearchError((e as Error).message);
        }

        if (!orchesty) {
            throw new SearchError('Entity not found!');
        }

        const deleteQuery = {
            instanceId: orchesty.instanceId,
        };

        await this.cloudService.deleteCloud(deleteQuery as ICloudSearchQuery);

        return super.delete(deleteQuery as IOrchestySearchQuery);
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
