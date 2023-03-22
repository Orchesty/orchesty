import { Request, Response } from 'express';
import { ObjectId } from 'mongodb';
import BaseEntity from '../entities/BaseEntity';
import { ResourceEnum } from '../enums/ResourceEnum';
import handleError from '../handlers/errorHandler';
import { preprocessRequestForAdmin } from '../security/securityService';
import BaseService from '../services/BaseService';

export interface ISearchQuery {
    _id?: ObjectId;
}

export async function list<
    IEntity extends BaseEntity,
    IQuery extends ISearchQuery,
>(service: BaseService<IEntity, IQuery>, req: Request, res: Response): Promise<{ rows: IEntity[] } | null> {
    try {
        const query = preprocessRequestForAdmin<IQuery>(req, ResourceEnum.SUPER_ADMIN);
        return await service.list(query);
    } catch (e) {
        handleError(e as Error, req, res);
        return null;
    }
}

export async function get<
    IEntity extends BaseEntity,
    IQuery extends ISearchQuery,
>(service: BaseService<IEntity, IQuery>, req: Request, res: Response): Promise<IEntity | null> {
    try {
        const query = preprocessRequestForAdmin<IQuery>(req, ResourceEnum.SUPER_ADMIN);
        return await service.get(query);
    } catch (e) {
        handleError(e as Error, req, res);
        return null;
    }
}

export async function create<
    IEntity extends BaseEntity,
    IQuery extends ISearchQuery,
>(service: BaseService<IEntity, IQuery>, req: Request, res: Response): Promise<IEntity | null> {
    try {
        const query = preprocessRequestForAdmin<IQuery>(req, ResourceEnum.SUPER_ADMIN);
        return await service.create(query);
    } catch (e) {
        handleError(e as Error, req, res);
        return null;
    }
}

export async function update<
    IEntity extends BaseEntity,
    IQuery extends ISearchQuery,
>(service: BaseService<IEntity, IQuery>, req: Request, res: Response): Promise<IEntity | null> {
    try {
        const query = preprocessRequestForAdmin<IQuery>(req, ResourceEnum.SUPER_ADMIN);
        return await service.update(query);
    } catch (e) {
        handleError(e as Error, req, res);
        return null;
    }
}

export async function remove<
    IEntity extends BaseEntity,
    IQuery extends ISearchQuery,
>(service: BaseService<IEntity, IQuery>, req: Request, res: Response): Promise<{ msg: string } | null> {
    try {
        const query = preprocessRequestForAdmin<IQuery>(req, ResourceEnum.SUPER_ADMIN);
        return await service.delete(query);
    } catch (e) {
        handleError(e as Error, req, res);
        return null;
    }
}
