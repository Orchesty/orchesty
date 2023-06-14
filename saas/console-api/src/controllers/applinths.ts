import { Request, Response } from 'express';
import Cloud from '../admin/entities/Cloud';
import ApplinthService from '../admin/services/ApplinthService';
import Services from '../base/DIContainer/Services';
import { ResourceEnum } from '../base/enums/ResourceEnum';
import handleError from '../base/handlers/errorHandler';
import { preprocessRequestForAdmin } from '../base/security/securityService';
import { container } from '../index';
import { get, ISearchQuery, list, update } from './baseController';

export interface IApplinthSearchQuery extends ISearchQuery {
    tenantId?: string | null;
    instanceId?: string | null;
    instanceDisplayName?: string;
    cloud?: Cloud;
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
    try {
        const applinth = await getApplinthsService().createApplinth(
            preprocessRequestForAdmin(req, ResourceEnum.SUPER_ADMIN),
        );
        res.status(200).send({ applinth });
    } catch (e) {
        handleError(e as Error, req, res);
    }
}

export async function updateApplinth(req: Request, res: Response): Promise<void> {
    const applinth = await update(getApplinthsService(), req, res);
    if (!applinth) {
        return;
    }
    res.status(200).send({ applinth });
}

export async function deleteApplinth(req: Request, res: Response): Promise<void> {
    try {
        const resp = await getApplinthsService().deleteApplinth(
            preprocessRequestForAdmin(req, ResourceEnum.SUPER_ADMIN),
        );
        res.status(200).send(resp);
    } catch (e) {
        handleError(e as Error, req, res);
    }
}
