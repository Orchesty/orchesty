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
            type: 'applinth_enduser_app_hearthbeat',
            version: '1',
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
            data: null,
            iid: '1',
            type: 'applinth_enduser_app_hearthbeat',
            version: '1',
        });
    });
    it('not supported event type', async () => {
        const server = container.get<Express>(Services.SERVER);
        const resp = await supertest(server).put('/').send({
            created: '2018-07-20T05:17:36Z',
            iid: '1',
            type: '1',
            version: '1',
        });
        assert.deepEqual(resp.statusCode, 400);
        assert.deepEqual(resp.text, '{"msg":"Not supported event type!"}');
    });
    it('missing data field', async () => {
        const server = container.get<Express>(Services.SERVER);
        const resp = await supertest(server).put('/').send({
            created: '2018-07-20T05:17:36Z',
            iid: '1',
            type: 'applinth_enduser_app_install',
            version: '1',
        });
        assert.deepEqual(resp.statusCode, 400);
        assert.deepEqual(resp.text, '{"msg":"Missing data field or required params in data for this type of Event!"}');
    });
    it('missing fields in data field', async () => {
        const server = container.get<Express>(Services.SERVER);
        const resp = await supertest(server).put('/').send({
            created: '2018-07-20T05:17:36Z',
            iid: '1',
            type: 'applinth_enduser_app_install',
            version: '1',
            data: { aid: '1' },
        });
        assert.deepEqual(resp.statusCode, 400);
        assert.deepEqual(resp.text, '{"msg":"Missing data field or required params in data for this type of Event!"}');

        const resp2 = await supertest(server).put('/').send({
            created: '2018-07-20T05:17:36Z',
            iid: '1',
            type: 'orchesty_operations',
            version: '1',
            data: { aid: '1', euid: '1' },
        });
        assert.deepEqual(resp2.statusCode, 400);
        assert.deepEqual(resp2.text, '{"msg":"Missing data field or required params in data for this type of Event!"}');
    });
});
