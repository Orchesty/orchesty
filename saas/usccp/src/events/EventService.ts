import Services from '../DIContainer/Services';
import { EventTypeEnum, getAllEventTypes } from '../enums/EventTypeEnum';
import EventError from '../errors/EventError';
import { container } from '../index';
import { logger } from '../logger/logger';

export const EVENTS_COLLECTION_NAME = 'Events';

export interface IEventBody {
    created: string;
    iid: string;
    type: EventTypeEnum;
    version: string;
    data?: {
        aid?: string;
        euid?: string;
        total?: number;
        day?: string;
    };
}

export interface IResponse {
    status: string;
}

export default class EventService {

    public async putEvent(body: IEventBody): Promise<IResponse> {
        if (!getAllEventTypes().includes(body.type)) {
            throw new EventError('Not supported event type!');
        }

        if ([
            EventTypeEnum.APPLINTH_END_USER_APP_INSTALL,
            EventTypeEnum.APPLINTH_END_USER_APP_UNINSTALL,
        ].includes(body.type) && (!body.data?.aid || !body.data?.euid)) {
            throw new EventError('Missing data field or required params in data for this type of Event!');
        }

        if (body.type === EventTypeEnum.ORCHESTY_OPERATIONS && (!body.data?.total || !body.data?.day)) {
            throw new EventError('Missing data field or required params in data for this type of Event!');
        }

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
