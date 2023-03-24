import { Request, Response } from 'express';
import Services from '../DIContainer/Services';
import { container } from '../index';
import OrchestyService from '../services/OrchestyService';
import { create, get, ISearchQuery, list, remove, update } from './baseController';

export interface IOrchestySearchQuery extends ISearchQuery {
    tenantId?: string | null;
    instanceId?: string | null;
}

function getOrchestrasService(): OrchestyService {
    return container.get<OrchestyService>(Services.ORCHESTY_SERVICE);
}

export async function orchestrasList(req: Request, res: Response): Promise<void> {
    const orchestras = await list(getOrchestrasService(), req, res);
    if (!orchestras) {
        return;
    }
    res.status(200).send(orchestras);
}

export async function getOrchesty(req: Request, res: Response): Promise<void> {
    const orchesty = await get(getOrchestrasService(), req, res);
    if (!orchesty) {
        return;
    }
    res.status(200).send({ orchesty });
}

export async function createOrchesty(req: Request, res: Response): Promise<void> {
    const orchesty = await create(getOrchestrasService(), req, res);
    if (!orchesty) {
        return;
    }
    res.status(200).send({ orchesty });
}

export async function updateOrchesty(req: Request, res: Response): Promise<void> {
    const orchesty = await update(getOrchestrasService(), req, res);
    if (!orchesty) {
        return;
    }
    res.status(200).send({ orchesty });
}

export async function deleteOrchesty(req: Request, res: Response): Promise<void> {
    const msg = await remove(getOrchestrasService(), req, res);
    if (!msg) {
        return;
    }
    res.status(200).send(msg);
}
