import { Request, Response } from 'express';
import Services from '../DIContainer/Services';
import { container } from '../index';
import CorrectionService from '../services/CorrectionService';
import { create, get, ISearchQuery, list, remove, update } from './baseController';

export interface ICorrectionSearchQuery extends ISearchQuery {
    tenantId?: string;
}

function getCorrectionsService(): CorrectionService {
    return container.get<CorrectionService>(Services.CORRECTIONS_SERVICE);
}

export async function correctionsList(req: Request, res: Response): Promise<void> {
    const corrections = await list(getCorrectionsService(), req, res);
    if (!corrections) {
        return;
    }
    res.status(200).send(corrections);
}

export async function getCorrection(req: Request, res: Response): Promise<void> {
    const correction = await get(getCorrectionsService(), req, res);
    if (!correction) {
        return;
    }
    res.status(200).send({ correction });
}

export async function createCorrection(req: Request, res: Response): Promise<void> {
    const correction = await create(getCorrectionsService(), req, res);
    if (!correction) {
        return;
    }
    res.status(200).send({ correction });
}

export async function updateCorrection(req: Request, res: Response): Promise<void> {
    const correction = await update(getCorrectionsService(), req, res);
    if (!correction) {
        return;
    }
    res.status(200).send({ correction });
}

export async function deleteCorrection(req: Request, res: Response): Promise<void> {
    const msg = await remove(getCorrectionsService(), req, res);
    if (!msg) {
        return;
    }
    res.status(200).send(msg);
}
