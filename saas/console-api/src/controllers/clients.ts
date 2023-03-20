import { Request, Response } from 'express';
import Services from '../DIContainer/Services';
import { IContact } from '../entities/Client';
import { ResourceEnum } from '../enums/ResourceEnum';
import handleError from '../handlers/errorHandler';
import { container } from '../index';
import { preprocessRequestForAdmin } from '../security/securityService';
import ClientService from '../services/ClientService';

export interface IClientCreateQuery extends IClientSearchQuery {
    hourlyRate: number;
    note: string;
}

export interface IClientSearchQuery {
    companyName?: string;
    iDokladId?: string;
    contact?: IContact;
    tenantId?: string;
    clientId?: string;
}

function getClientsService(): ClientService {
    return container.get<ClientService>(Services.CLIENTS_SERVICE);
}

export async function clientsList(req: Request, res: Response): Promise<void> {
    try {
        const query = preprocessRequestForAdmin<IClientSearchQuery>(req, ResourceEnum.SUPER_ADMIN);
        res.status(200).send(await getClientsService().getClientsList(query));
    } catch (e) {
        handleError(e as Error, req, res);
    }
}

export async function getClient(req: Request, res: Response): Promise<void> {
    try {
        const query = preprocessRequestForAdmin<IClientSearchQuery>(req, ResourceEnum.SUPER_ADMIN);
        res.status(200).send(await getClientsService().getClient(query));
    } catch (e) {
        handleError(e as Error, req, res);
    }
}

export async function createClient(req: Request, res: Response): Promise<void> {
    try {
        const query = preprocessRequestForAdmin<IClientCreateQuery>(req, ResourceEnum.SUPER_ADMIN);
        res.status(200).send(await getClientsService().createClient(query));
    } catch (e) {
        handleError(e as Error, req, res);
    }
}

export async function updateClient(req: Request, res: Response): Promise<void> {
    try {
        const query = preprocessRequestForAdmin<IClientCreateQuery>(req, ResourceEnum.SUPER_ADMIN);
        res.status(200).send(await getClientsService().updateClient(query));
    } catch (e) {
        handleError(e as Error, req, res);
    }
}

export async function deleteClient(req: Request, res: Response): Promise<void> {
    try {
        const query = preprocessRequestForAdmin<IClientSearchQuery>(req, ResourceEnum.SUPER_ADMIN);
        res.status(200).send(await getClientsService().deleteClient(query));
    } catch (e) {
        handleError(e as Error, req, res);
    }
}
