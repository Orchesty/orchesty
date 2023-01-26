import assert from 'assert';
import { Express } from 'express';
import supertest from 'supertest';
import Services from '../../DIContainer/Services';
import { EVENTS_COLLECTION_NAME } from '../../events/EventService';
import { container } from '../../index';
import Storage from '../../storage/Storage';

describe('eventController', () => {
    it('shouldReturn200', async () => {
        const server = container.get<Express>(Services.SERVER);
        const resp = await supertest(server).put('/').send({
            created: '2018-07-20T05:17:36Z',
            iid: '1',
            type: '1',
            version: '1',
            data: ['1'],
        });
        assert.deepEqual(resp.statusCode, 200);
        const storage = container.get<Storage>(Services.STORAGE);
        const inserted = await storage.getUSDb().collection(EVENTS_COLLECTION_NAME).findOne({
            iid: '1',
            created: '2018-07-20T05:17:36Z',
        });
        assert.deepEqual(inserted, {
            _id: inserted?._id,
            created: '2018-07-20T05:17:36Z',
            iid: '1',
            type: '1',
            version: '1',
            data: ['1'],
        });
    });
});
