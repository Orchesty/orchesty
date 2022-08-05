import { Request, Response } from 'express';
import handleError from '../handlers/errorHandler';
import { tenantService } from '../index';
import { getLoggedUser, getLoggedUserPermissions, hasPermission } from '../security/securityService';
import { ResourceEnum } from '../enums/ResourceEnum';
import PermissionsError from '../errors/PermissionsError';

export interface ITenantCreateRequest {
  displayName?: string,
  email?: string,
  userDisplayName?: string,
}

export interface ITenantSearchQuery {
  tenantId?: string,
}

function preprocessRequest(req: Request, permission: ResourceEnum) {
  if (!hasPermission(getLoggedUserPermissions(req), permission)) {
    throw new PermissionsError();
  }
}

function preprocessRequestWithQuery(req: Request, permission: ResourceEnum) {
  preprocessRequest(req, permission);
  const tenantId = getLoggedUser(req);
  const query = req.params as unknown as ITenantSearchQuery;

  if (permission === ResourceEnum.DELETE_TENANT && query.tenantId === tenantId) {
    throw new PermissionsError('Tenant cannot delete himself!');
  }

  if (tenantId !== query.tenantId
    && !hasPermission(getLoggedUserPermissions(req), ResourceEnum.USE_ANOTHER_TENANT_ID)) {
    throw new PermissionsError();
  }

  return { query, tenantId };
}

export const tenantsList = async (req: Request, res: Response) => {
  try {
    preprocessRequest(req, ResourceEnum.TENANTS_LIST_ALL);
    res.status(200).send(await tenantService.getTenantList());
  } catch (e) {
    handleError(e as Error, req, res);
  }
};

export const tenantsGet = async (req: Request, res: Response) => {
  try {
    const { query, tenantId } = preprocessRequestWithQuery(req, ResourceEnum.GET_TENANT);
    res.status(200).send(await tenantService.getTenant(query.tenantId ?? tenantId));
  } catch (e) {
    handleError(e as Error, req, res);
  }
};

export const tenantsCreate = async (req: Request, res: Response) => {
  try {
    preprocessRequest(req, ResourceEnum.CREATE_TENANT);
    const tenantCreateRequest = req.body as ITenantCreateRequest;
    res.status(200).send(await tenantService.createTenant(tenantCreateRequest));
  } catch (e) {
    handleError(e as Error, req, res);
  }
};

export const tenantsUpdate = async (req: Request, res: Response) => {
  try {
    const { query, tenantId } = preprocessRequestWithQuery(req, ResourceEnum.UPDATE_TENANT);
    const tenantUpdateRequest = req.body as ITenantCreateRequest;
    res.status(200).send(await tenantService.updateTenant(query.tenantId ?? tenantId, tenantUpdateRequest));
  } catch (e) {
    handleError(e as Error, req, res);
  }
};

export const tenantsDelete = async (req: Request, res: Response) => {
  try {
    const { query } = preprocessRequestWithQuery(req, ResourceEnum.DELETE_TENANT);
    res.status(200).send(await tenantService.deleteTenant(query.tenantId ?? ''));
  } catch (e) {
    handleError(e as Error, req, res);
  }
};
