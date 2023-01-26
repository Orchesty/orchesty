import { Request, Response } from 'express';
import Services from '../DIContainer/Services';
import { ResourceEnum } from '../enums/ResourceEnum';
import handleError from '../handlers/errorHandler';
import { container } from '../index';
import { preprocessRequest } from '../security/securityService';
import UsersService from '../users/UsersService';

export interface IUserCreateParams extends IUserUpdateParams {
    email?: string;
}

export interface IUserUpdateParams {
    customTenantId?: string;
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
    gTenantId?: string;
}

function getUsersService(): UsersService {
    return container.get<UsersService>(Services.USERS_SERVICE);
}

export async function usersList(req: Request, res: Response): Promise<void> {
    try {
        const { query, gTenantId } = await preprocessRequest<IUserSearchQuery>(req, ResourceEnum.USERS_SEARCH);
        res.status(200).send(await getUsersService().getUsersList(query, gTenantId));
    } catch (e) {
        handleError(e as Error, req, res);
    }
}

export async function usersGet(req: Request, res: Response): Promise<void> {
    try {
        const { query, gTenantId } = await preprocessRequest<IUserSearchQuery>(req, ResourceEnum.GET_USER);
        res.status(200).send(await getUsersService().getUser(query, gTenantId));
    } catch (e) {
        handleError(e as Error, req, res);
    }
}

export async function usersCreate(req: Request, res: Response): Promise<void> {
    try {
        const { query, gTenantId, tenantId } = await preprocessRequest<IUserSearchQuery>(req, ResourceEnum.CREATE_USER);
        res.status(200).send(await getUsersService().createUser(
            query,
            req.body as unknown as IUserCreateParams,
            gTenantId,
            tenantId,
        ));
    } catch (e) {
        handleError(e as Error, req, res);
    }
}

export async function usersUpdate(req: Request, res: Response): Promise<void> {
    try {
        const { query, gTenantId } = await preprocessRequest<IUserSearchQuery>(req, ResourceEnum.UPDATE_USER);
        res.status(200)
            .send(await getUsersService().updateUser(query, req.body as unknown as IUserUpdateParams, gTenantId));
    } catch (e) {
        handleError(e as Error, req, res);
    }
}

export async function usersDelete(req: Request, res: Response): Promise<void> {
    try {
        const { query, gTenantId } = await preprocessRequest<IUserSearchQuery>(req, ResourceEnum.DELETE_USER);
        res.status(200).send(await getUsersService().deleteUser(query, gTenantId));
    } catch (e) {
        handleError(e as Error, req, res);
    }
}

export async function userSendResetPasswordEmail(req: Request, res: Response): Promise<void> {
    try {
        const { query, gTenantId } = await preprocessRequest<IUserSearchQuery>(
            req,
            ResourceEnum.GENERATE_RESET_PASSWORD_LINK,
        );
        res.status(200).send(await getUsersService().sendResetPasswordEmail(query, gTenantId));
    } catch (e) {
        handleError(e as Error, req, res);
    }
}

export async function userGetGTenantId(req: Request, res: Response): Promise<void> {
    try {
        const { tenantId } = req.params;
        res.status(200).send(await getUsersService().getGTenantId(tenantId));
    } catch (e) {
        handleError(e as Error, req, res);
    }
}
