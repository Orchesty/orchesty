import { Request, Response } from 'express';
import Cloud from '../admin/entities/Cloud';
import OrchestyService from '../admin/services/OrchestyService';
import Services from '../base/DIContainer/Services';
import { ResourceEnum } from '../base/enums/ResourceEnum';
import handleError from '../base/handlers/errorHandler';
import { preprocessRequestForAdmin } from '../base/security/securityService';
import { container } from '../index';
import { get, ISearchQuery, list, update } from './baseController';

export interface IOrchestySearchQuery extends ISearchQuery {
    tenantId?: string | null;
    instanceId?: string | null;
    instanceDisplayName?: string;
    cloud?: Cloud;
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
    try {
        const orchesty = await getOrchestrasService().createOrchesty(
            preprocessRequestForAdmin(req, ResourceEnum.SUPER_ADMIN),
        );
        res.status(200).send({ orchesty });
    } catch (e) {
        handleError(e as Error, req, res);
    }
}

export async function updateOrchesty(req: Request, res: Response): Promise<void> {
    const orchesty = await update(getOrchestrasService(), req, res);
    if (!orchesty) {
        return;
    }
    res.status(200).send({ orchesty });
}

export async function deleteOrchesty(req: Request, res: Response): Promise<void> {
    try {
        const resp = await getOrchestrasService().deleteOrchesty(
            preprocessRequestForAdmin(req, ResourceEnum.SUPER_ADMIN),
        );
        res.status(200).send(resp);
    } catch (e) {
        handleError(e as Error, req, res);
    }
}
