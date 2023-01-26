import { Request, Response } from 'express';
import Services from '../DIContainer/Services';
import { ResourceEnum } from '../enums/ResourceEnum';
import handleError from '../handlers/errorHandler';
import { container } from '../index';
import { preprocessRequest } from '../security/securityService';
import TenantService from '../tenants/TenantService';

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

function getTenantService(): TenantService {
    return container.get<TenantService>(Services.TENANT_SERVICE);
}

export async function tenantsList(req: Request, res: Response): Promise<void> {
    try {
        await preprocessRequest<ITenantSearchQuery>(req, ResourceEnum.TENANTS_LIST_ALL);
        res.status(200).send(await getTenantService().getTenantList());
    } catch (e) {
        handleError(e as Error, req, res);
    }
}

export async function tenantsGet(req: Request, res: Response): Promise<void> {
    try {
        const { query, gTenantId } = await preprocessRequest<ITenantSearchQuery>(req, ResourceEnum.GET_TENANT);
        res.status(200)
            .send(await getTenantService().getTenant(query.gTenantId ?? gTenantId));
    } catch (e) {
        handleError(e as Error, req, res);
    }
}

export async function tenantsCreate(req: Request, res: Response): Promise<void> {
    try {
        await preprocessRequest<ITenantSearchQuery>(req, ResourceEnum.CREATE_TENANT);
        const tenantCreateRequest = req.body as ITenantCreateRequest;
        res.status(200)
            .send(await getTenantService().createTenant(tenantCreateRequest));
    } catch (e) {
        handleError(e as Error, req, res);
    }
}

export async function tenantsUpdate(req: Request, res: Response): Promise<void> {
    try {
        const { query, gTenantId } = await preprocessRequest<ITenantSearchQuery>(req, ResourceEnum.UPDATE_TENANT);
        const tenantUpdateRequest = req.body as ITenantCreateRequest;
        res.status(200).send(
            await container
                .get<TenantService>(Services.TENANT_SERVICE)
                .updateTenant(query.gTenantId ?? gTenantId, tenantUpdateRequest),
        );
    } catch (e) {
        handleError(e as Error, req, res);
    }
}

export async function tenantsDelete(req: Request, res: Response): Promise<void> {
    try {
        const { query } = await preprocessRequest<ITenantSearchQuery>(req, ResourceEnum.DELETE_TENANT);
        res.status(200)
            .send(await getTenantService().deleteTenant(query.gTenantId ?? ''));
    } catch (e) {
        handleError(e as Error, req, res);
    }
}
