import { Request, Response } from 'express';

export function status(req: Request, res: Response): void {
    res.status(200).send();
}
