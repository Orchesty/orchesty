import assert from 'assert';
import supertest from 'supertest';
import { init, IServices } from '../../src';

let services: IServices;
describe('Tests for TestBatch', () => {
    beforeAll(async () => {
        services = await init();
    });

    afterAll(async () => {
        await services.mongo.disconnect();
    });

    it('status - /', async () => {
        const resp = await supertest(services.app).get('/');
        assert.equal(resp.statusCode, 200);
        assert.deepEqual(resp.body, { mongo: { connected: true } });
    });

    it('status - /status', async () => {
        const resp = await supertest(services.app).get('/status');
        assert.equal(resp.statusCode, 200);
        assert.deepEqual(resp.body, { mongo: { connected: true } });
    });
});
