import { Request, Response } from 'express';
import Services from '../DIContainer/Services';
import { CloudPlan } from '../enums/CloudPlan';
import { Period } from '../enums/Period';
import { container } from '../index';
import CloudService from '../services/CloudService';
import { create, get, ISearchQuery, list, remove, update } from './baseController';

export interface ICloudSearchQuery extends ISearchQuery {
    clientId?: string | null;
    plan?: CloudPlan | null;
    price?: number | null;
    period?: Period | null;
    startDate?: Date | null;
    closeDate?: Date | null;
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

export async function createCloud(req: Request, res: Response): Promise<void> {
    const cloud = await create(getCloudsService(), req, res);
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

export async function deleteCloud(req: Request, res: Response): Promise<void> {
    const msg = await remove(getCloudsService(), req, res);
    if (!msg) {
        return;
    }
    res.status(200).send(msg);
}
