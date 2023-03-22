import { Request, Response } from 'express';
import Services from '../DIContainer/Services';
import { container } from '../index';
import ApplinthService from '../services/ApplinthService';
import { create, get, ISearchQuery, list, remove, update } from './baseController';

export interface IApplinthSearchQuery extends ISearchQuery {
    clientId?: string | null;
    cloudId?: string | null;
    startDate?: Date | null;
    minPrice?: number | null;
    minPriceDate?: Date | null;
}

function getApplinthsService(): ApplinthService {
    return container.get<ApplinthService>(Services.APPLINTH_SERVICE);
}

export async function applinthsList(req: Request, res: Response): Promise<void> {
    const applinths = await list(getApplinthsService(), req, res);
    if (!applinths) {
        return;
    }
    res.status(200).send(applinths);
}

export async function getApplinth(req: Request, res: Response): Promise<void> {
    const applinth = await get(getApplinthsService(), req, res);
    if (!applinth) {
        return;
    }
    res.status(200).send({ applinth });
}

export async function createApplinth(req: Request, res: Response): Promise<void> {
    const applinth = await create(getApplinthsService(), req, res);
    if (!applinth) {
        return;
    }
    res.status(200).send({ applinth });
}

export async function updateApplinth(req: Request, res: Response): Promise<void> {
    const applinth = await update(getApplinthsService(), req, res);
    if (!applinth) {
        return;
    }
    res.status(200).send({ applinth });
}

export async function deleteApplinth(req: Request, res: Response): Promise<void> {
    const msg = await remove(getApplinthsService(), req, res);
    if (!msg) {
        return;
    }
    res.status(200).send(msg);
}
