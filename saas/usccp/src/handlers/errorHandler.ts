import { Request, Response } from 'express';

export default function handleError(err: Error, req: Request, res: Response): Response {
    return res.status(500).send({ msg: err.message });
}
