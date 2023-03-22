import { Request, Response } from 'express';
import Services from '../DIContainer/Services';
import { container } from '../index';
import SupportService from '../services/SupportService';
import { create, get, ISearchQuery, list, remove, update } from './baseController';

export interface ISupportSearchQuery extends ISearchQuery {
    clientId?: string;
}

function getSupportsService(): SupportService {
    return container.get<SupportService>(Services.SUPPORTS_SERVICE);
}

export async function supportsList(req: Request, res: Response): Promise<void> {
    const supports = await list(getSupportsService(), req, res);
    if (!supports) {
        return;
    }
    res.status(200).send(supports);
}

export async function getSupport(req: Request, res: Response): Promise<void> {
    const support = await get(getSupportsService(), req, res);
    if (!support) {
        return;
    }
    res.status(200).send({ support });
}

export async function createSupport(req: Request, res: Response): Promise<void> {
    const support = await create(getSupportsService(), req, res);
    if (!support) {
        return;
    }
    res.status(200).send({ support });
}

export async function updateSupport(req: Request, res: Response): Promise<void> {
    const support = await update(getSupportsService(), req, res);
    if (!support) {
        return;
    }
    res.status(200).send({ support });
}

export async function deleteSupport(req: Request, res: Response): Promise<void> {
    const msg = await remove(getSupportsService(), req, res);
    if (!msg) {
        return;
    }
    res.status(200).send(msg);
}
