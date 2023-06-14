import axios from 'axios';
import { app } from '../../base/config/config';
import { CollectionEnum } from '../../base/enums/CollectionEnum';
import Mongo from '../../base/storage/mongo/Mongo';
import { ICloudSearchQuery } from '../../controllers/clouds';
import Cloud from '../entities/Cloud';
import CreationError from '../errors/CreationError';
import DeleteError from '../errors/DeleteError';
import BaseService from './BaseService';

export default class CloudService extends BaseService<Cloud, ICloudSearchQuery> {

    public constructor(db: Mongo) {
        super(db.getCloudCollection(CollectionEnum.CLOUD));
    }

    public async createCloud(query: ICloudSearchQuery): Promise<Cloud> {
        const usageStatsEvent: IUsageStatsEvent = {
            created: new Date().toISOString(),
            iid: query.instanceId ?? '',
            type: 'cloud_install',
            version: 1,
        };

        const { usccpUri } = app;

        const resp = await axios.put(
            usccpUri,
            {
                body: JSON.stringify(usageStatsEvent),
                headers: {
                    'Content-Type': 'application/json',
                },
            },
        );

        if (resp.status >= 300) {
            throw new CreationError('Could not send event to USCCP!');
        }

        return super.create(query);
    }

    public async deleteCloud(query: ICloudSearchQuery): Promise<void> {
        const usageStatsEvent: IUsageStatsEvent = {
            created: new Date().toISOString(),
            iid: query.instanceId ?? '',
            type: 'cloud_delete',
            version: 1,
        };

        const resp = await axios.put(
            app.usccpUri,
            {
                body: JSON.stringify(usageStatsEvent),
                headers: {
                    'Content-Type': 'application/json',
                },
            },
        );

        if (resp.status >= 300) {
            throw new DeleteError('Could not send event to USCCP!');
        }

        await super.delete(query);
    }

    protected mapRecordToExport(cloud: Cloud): Cloud {
        return {
            ...super.mapRecordToExport(cloud),
            tenantId: cloud.tenantId,
            plan: cloud.plan,
            price: cloud.price,
            period: cloud.period,
            instanceId: cloud.instanceId,
            startDate: cloud.startDate,
            closeDate: cloud.closeDate ?? null,
        };
    }

    protected getEntityDateFields(): string[] {
        return [...super.getEntityDateFields(), 'startDate', 'closeDate'];
    }

}

export interface IUsageStatsEvent {
    created: string;
    iid: string;
    type: string;
    version: number;
}
