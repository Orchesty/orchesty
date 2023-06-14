import { Request, Response } from 'express';
import CloudService from '../admin/services/CloudService';
import Services from '../base/DIContainer/Services';
import { container } from '../index';
import { get, ISearchQuery, list, update } from './baseController';

export interface ICloudSearchQuery extends ISearchQuery {
    tenantId?: string | null;
    instanceId?: string;
}

function getCloudsService(): CloudService {
    return container.get<CloudService>(Services.CLOUD_SERVICE);
}

export async function cloudsList(req: Request, res: Response): Promise<void> {
    const clouds = await list(getCloudsService(), req, res);
    if (!clouds) {
        return;
    }
    res.status(200).send(clouds);
}

export async function getCloud(req: Request, res: Response): Promise<void> {
    const cloud = await get(getCloudsService(), req, res);
    if (!cloud) {
        return;
    }
    res.status(200).send({ cloud });
}

export async function updateCloud(req: Request, res: Response): Promise<void> {
    const cloud = await update(getCloudsService(), req, res);
    if (!cloud) {
        return;
    }
    res.status(200).send({ cloud });
}
