import Services from '../DIContainer/Services';
import { container } from '../index';
import { logger } from '../logger/logger';

export const EVENTS_COLLECTION_NAME = 'Events';

export interface IEventBody {
    created: string;
    iid: string;
    type: string;
    version: string;
    data: unknown;
}

export interface IResponse {
    status: string;
}

export default class EventService {

    public async putEvent(body: IEventBody): Promise<IResponse> {
        const storage = container.get<Storage>(Services.STORAGE);
        const usdb = storage.getUSDb();
        const eventsCol = usdb.collection(EVENTS_COLLECTION_NAME);
        const payload = body;

        if (await eventsCol.findOne({
            iid: payload.iid,
            created: payload.created,
        })) {
            logger.info(['dup', payload]);
            // skip dupes silently
            return { status: 'ok' };
        }

        logger.info(['insert', payload]);
        await eventsCol.insertOne({
            created: payload.created,
            iid: payload.iid,
            type: payload.type,
            version: payload.version,
            data: payload.data,
        });

        return { status: 'ok' };
    }

}
