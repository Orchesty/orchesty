import { Request, Response } from 'express';
import Services from '../DIContainer/Services';
import EventService from '../events/EventService';
import handleError from '../handlers/errorHandler';
import { container } from '../index';

export async function putEvent(req: Request, res: Response): Promise<Response> {
    try {
        const eventService = container.get<EventService>(Services.EVENT_SERVICE);
        return res.status(200).send(await eventService.putEvent(req.body));
    } catch (e) {
        return handleError(e as Error, req, res);
    }
}
