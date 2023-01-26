import { Request, Response } from 'express';

export function getStatus(req: Request, res: Response): void {
    res.status(200).send({ status: 'ok' });
}
