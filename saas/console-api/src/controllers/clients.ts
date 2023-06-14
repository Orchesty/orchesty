import { Request, Response } from 'express';
import ClientService from '../admin/services/ClientService';
import Services from '../base/DIContainer/Services';
import { container } from '../index';
import { create, get, ISearchQuery, list, remove, update } from './baseController';

export interface IClientSearchQuery extends ISearchQuery {
    tenantId?: string;
    companyName?: string;
    invoicingId?: string;
}

function getClientsService(): ClientService {
    return container.get<ClientService>(Services.CLIENTS_SERVICE);
}

export async function clientsList(req: Request, res: Response): Promise<void> {
    const clients = await list(getClientsService(), req, res);
    if (!clients) {
        return;
    }
    res.status(200).send(clients);
}

export async function getClient(req: Request, res: Response): Promise<void> {
    const client = await get(getClientsService(), req, res);
    if (!client) {
        return;
    }
    res.status(200).send({ client });
}

export async function createClient(req: Request, res: Response): Promise<void> {
    const client = await create(getClientsService(), req, res);
    if (!client) {
        return;
    }
    res.status(200).send({ client });
}

export async function updateClient(req: Request, res: Response): Promise<void> {
    const client = await update(getClientsService(), req, res);
    if (!client) {
        return;
    }
    res.status(200).send({ client });
}

export async function deleteClient(req: Request, res: Response): Promise<void> {
    const msg = await remove(getClientsService(), req, res);
    if (!msg) {
        return;
    }
    res.status(200).send(msg);
}
