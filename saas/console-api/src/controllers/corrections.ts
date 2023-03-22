import { Request, Response } from 'express';
import Services from '../DIContainer/Services';
import { container } from '../index';
import CorrectionsService from '../services/CorrectionsService';
import { create, get, ISearchQuery, list, remove, update } from './baseController';

export interface ICorrectionSearchQuery extends ISearchQuery {
    clientId?: string;
    date?: Date;
}

function getCorrectionsService(): CorrectionsService {
    return container.get<CorrectionsService>(Services.CORRECTIONS_SERVICE);
}

export async function correctionsList(req: Request, res: Response): Promise<void> {
    const corrections = await list(getCorrectionsService(), req, res);
    if (!corrections) {
        return;
    }
    res.status(200).send(corrections);
}

export async function getCorrection(req: Request, res: Response): Promise<void> {
    const support = await get(getCorrectionsService(), req, res);
    if (!support) {
        return;
    }
    res.status(200).send({ support });
}

export async function createCorrection(req: Request, res: Response): Promise<void> {
    const support = await create(getCorrectionsService(), req, res);
    if (!support) {
        return;
    }
    res.status(200).send({ support });
}

export async function updateCorrection(req: Request, res: Response): Promise<void> {
    const support = await update(getCorrectionsService(), req, res);
    if (!support) {
        return;
    }
    res.status(200).send({ support });
}

export async function deleteCorrection(req: Request, res: Response): Promise<void> {
    const msg = await remove(getCorrectionsService(), req, res);
    if (!msg) {
        return;
    }
    res.status(200).send(msg);
}
