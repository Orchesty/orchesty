import { Request, Response } from 'express';
import Services from '../DIContainer/Services';
import { container } from '../index';
import ModuleService from '../services/ModuleService';
import { create, get, ISearchQuery, list, remove, update } from './baseController';

export interface IModuleSearchQuery extends ISearchQuery {
    appName?: string;
    applinthId?: string;
}

function getModulesService(): ModuleService {
    return container.get<ModuleService>(Services.MODULE_SERVICE);
}

export async function modulesList(req: Request, res: Response): Promise<void> {
    const modules = await list(getModulesService(), req, res);
    if (!modules) {
        return;
    }
    res.status(200).send(modules);
}

export async function getModule(req: Request, res: Response): Promise<void> {
    const module = await get(getModulesService(), req, res);
    if (!module) {
        return;
    }
    res.status(200).send({ module });
}

export async function createModule(req: Request, res: Response): Promise<void> {
    const module = await create(getModulesService(), req, res);
    if (!module) {
        return;
    }
    res.status(200).send({ module });
}

export async function updateModule(req: Request, res: Response): Promise<void> {
    const module = await update(getModulesService(), req, res);
    if (!module) {
        return;
    }
    res.status(200).send({ module });
}

export async function deleteModule(req: Request, res: Response): Promise<void> {
    const msg = await remove(getModulesService(), req, res);
    if (!msg) {
        return;
    }
    res.status(200).send(msg);
}
