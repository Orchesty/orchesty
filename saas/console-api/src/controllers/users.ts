import { Request, Response } from 'express';
import { ResourceEnum } from '../enums/ResourceEnum';
import handleError from '../handlers/errorHandler';
import { usersService } from '../index';
import { preprocessRequest } from '../security/securityService';

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

export async function usersList(req: Request, res: Response): Promise<void> {
    try {
        const { query, gTenantId } = await preprocessRequest<IUserSearchQuery>(req, ResourceEnum.USERS_SEARCH);
        res.status(200).send(await usersService.getUsersList(query, gTenantId));
    } catch (e) {
        handleError(e as Error, req, res);
    }
}

export async function usersGet(req: Request, res: Response): Promise<void> {
    try {
        const { query, gTenantId } = await preprocessRequest<IUserSearchQuery>(req, ResourceEnum.GET_USER);
        res.status(200).send(await usersService.getUser(query, gTenantId));
    } catch (e) {
        handleError(e as Error, req, res);
    }
}

export async function usersCreate(req: Request, res: Response): Promise<void> {
    try {
        const { query, gTenantId, tenantId } = await preprocessRequest<IUserSearchQuery>(req, ResourceEnum.CREATE_USER);
        res.status(200).send(await usersService.createUser(
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
        res.status(200).send(await usersService.updateUser(query, req.body as unknown as IUserUpdateParams, gTenantId));
    } catch (e) {
        handleError(e as Error, req, res);
    }
}

export async function usersDelete(req: Request, res: Response): Promise<void> {
    try {
        const { query, gTenantId } = await preprocessRequest<IUserSearchQuery>(req, ResourceEnum.DELETE_USER);
        res.status(200).send(await usersService.deleteUser(query, gTenantId));
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
        res.status(200).send(await usersService.sendResetPasswordEmail(query, gTenantId));
    } catch (e) {
        handleError(e as Error, req, res);
    }
}
