import { Request, Response } from 'express';
import EventError from '../errors/EventError';

export default function handleError(err: Error, req: Request, res: Response): Response {
    if (err instanceof EventError) {
        return res.status(400).send({ msg: err.message });
    }

    return res.status(500).send({ msg: err.message });
}
