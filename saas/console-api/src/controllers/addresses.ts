import { Request, Response } from 'express';
import AddressService from '../admin/services/AddressService';
import Services from '../base/DIContainer/Services';
import { container } from '../index';
import { create, get, ISearchQuery, list, remove, update } from './baseController';

export interface IAddressSearchQuery extends ISearchQuery {
    tenantId?: string;
    title?: string;
}

function getAddressesService(): AddressService {
    return container.get<AddressService>(Services.ADDRESS_SERVICE);
}

export async function addressesList(req: Request, res: Response): Promise<void> {
    const addresses = await list(getAddressesService(), req, res);
    if (!addresses) {
        return;
    }
    res.status(200).send(addresses);
}

export async function getAddress(req: Request, res: Response): Promise<void> {
    const address = await get(getAddressesService(), req, res);
    if (!address) {
        return;
    }
    res.status(200).send({ address });
}

export async function createAddress(req: Request, res: Response): Promise<void> {
    const address = await create(getAddressesService(), req, res);
    if (!address) {
        return;
    }
    res.status(200).send({ address });
}

export async function updateAddress(req: Request, res: Response): Promise<void> {
    const address = await update(getAddressesService(), req, res);
    if (!address) {
        return;
    }
    res.status(200).send({ address });
}

export async function deleteAddress(req: Request, res: Response): Promise<void> {
    const msg = await remove(getAddressesService(), req, res);
    if (!msg) {
        return;
    }
    res.status(200).send(msg);
}
