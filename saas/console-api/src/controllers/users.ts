import { Request, Response } from 'express';
import { ResourceEnum } from '../enums/ResourceEnum';
import PermissionsError from '../errors/PermissionsError';
import handleError from '../handlers/errorHandler';
import { usersService } from '../index';
import { getLoggedUser, getLoggedUserPermissions, hasPermission } from '../security/securityService';

export interface IUserCreateParams extends IUserUpdateParams {
    email?: string;
}

export interface IUserUpdateParams {
    phoneNumber?: string;
    displayName?: string;
    photoUrl?: string;
    disabled?: boolean;
}

export interface IUserSearchQuery {
    emails?: string;
    email?: string;
    uid?: string;
    tenantId?: string;
}

function preprocessRequest(req: Request, permission: ResourceEnum): { query: IUserSearchQuery; tenantId: string } {
    const tenantId = getLoggedUser(req);
    const query = { ...req.query, ...req.params } as unknown as IUserSearchQuery;
    const permissions = getLoggedUserPermissions(req);

    let allowed = true;

    if (!hasPermission(permissions, permission)) {
        allowed = false;
    } else if (query.tenantId && query.tenantId !== tenantId) {
        allowed = hasPermission(permissions, ResourceEnum.USE_ANOTHER_TENANT_ID);
    }

    if (!allowed) {
        throw new PermissionsError();
    }

    return { query, tenantId };
}

export async function usersList(req: Request, res: Response): Promise<void> {
    try {
        const { query, tenantId } = preprocessRequest(req, ResourceEnum.USERS_SEARCH);
        res.status(200).send(await usersService.getUsersList(query, tenantId));
    } catch (e) {
        handleError(e as Error, req, res);
    }
}

export async function usersGet(req: Request, res: Response): Promise<void> {
    try {
        const { query, tenantId } = preprocessRequest(req, ResourceEnum.GET_USER);
        res.status(200).send(await usersService.getUser(query, tenantId));
    } catch (e) {
        handleError(e as Error, req, res);
    }
}

export async function usersCreate(req: Request, res: Response): Promise<void> {
    try {
        const { query, tenantId } = preprocessRequest(req, ResourceEnum.CREATE_USER);
        res.status(200).send(await usersService.createUser(query, req.body as unknown as IUserCreateParams, tenantId));
    } catch (e) {
        handleError(e as Error, req, res);
    }
}

export async function usersUpdate(req: Request, res: Response): Promise<void> {
    try {
        const { query, tenantId } = preprocessRequest(req, ResourceEnum.UPDATE_USER);
        res.status(200).send(await usersService.updateUser(query, req.body as unknown as IUserUpdateParams, tenantId));
    } catch (e) {
        handleError(e as Error, req, res);
    }
}

export async function usersDelete(req: Request, res: Response): Promise<void> {
    try {
        const { query, tenantId } = preprocessRequest(req, ResourceEnum.DELETE_USER);
        res.status(200).send(await usersService.deleteUser(query, tenantId));
    } catch (e) {
        handleError(e as Error, req, res);
    }
}

export async function userSendResetPasswordEmail(req: Request, res: Response): Promise<void> {
    try {
        const { query, tenantId } = preprocessRequest(req, ResourceEnum.GENERATE_RESET_PASSWORD_LINK);
        res.status(200).send(await usersService.sendResetPasswordEmail(query, tenantId));
    } catch (e) {
        handleError(e as Error, req, res);
    }
}
