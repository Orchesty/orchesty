import { Request, Response } from 'express';
import { ResourceEnum } from '../enums/ResourceEnum';
import handleError from '../handlers/errorHandler';
import { tenantService } from '../index';
import { preprocessRequest } from '../security/securityService';

export interface ITenantCreateRequest {
    displayName?: string;
    email?: string;
    userDisplayName?: string;
}

export interface ITenantSearchQuery {
    instanceId?: string;
    tenantId?: string;
    gTenantId?: string;
}

export async function tenantsList(req: Request, res: Response): Promise<void> {
    try {
        await preprocessRequest<ITenantSearchQuery>(req, ResourceEnum.TENANTS_LIST_ALL);
        res.status(200).send(await tenantService.getTenantList());
    } catch (e) {
        handleError(e as Error, req, res);
    }
}

export async function tenantsGet(req: Request, res: Response): Promise<void> {
    try {
        const { query, gTenantId } = await preprocessRequest<ITenantSearchQuery>(req, ResourceEnum.GET_TENANT);
        res.status(200).send(await tenantService.getTenant(query.gTenantId ?? gTenantId));
    } catch (e) {
        handleError(e as Error, req, res);
    }
}

export async function tenantsCreate(req: Request, res: Response): Promise<void> {
    try {
        await preprocessRequest<ITenantSearchQuery>(req, ResourceEnum.CREATE_TENANT);
        const tenantCreateRequest = req.body as ITenantCreateRequest;
        res.status(200).send(await tenantService.createTenant(tenantCreateRequest));
    } catch (e) {
        handleError(e as Error, req, res);
    }
}

export async function tenantsUpdate(req: Request, res: Response): Promise<void> {
    try {
        const { query, gTenantId } = await preprocessRequest<ITenantSearchQuery>(req, ResourceEnum.UPDATE_TENANT);
        const tenantUpdateRequest = req.body as ITenantCreateRequest;
        res.status(200).send(await tenantService.updateTenant(query.gTenantId ?? gTenantId, tenantUpdateRequest));
    } catch (e) {
        handleError(e as Error, req, res);
    }
}

export async function tenantsDelete(req: Request, res: Response): Promise<void> {
    try {
        const { query } = await preprocessRequest<ITenantSearchQuery>(req, ResourceEnum.DELETE_TENANT);
        res.status(200).send(await tenantService.deleteTenant(query.gTenantId ?? ''));
    } catch (e) {
        handleError(e as Error, req, res);
    }
}
