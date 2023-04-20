import assert from 'assert';
import supertest from 'supertest';
import { init, IServices } from '../../src';
import { ORCHESTY_API_KEY } from '../../src/authorization/AuthorizationMiddleware';

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
        const resp = await supertest(services.app).get('/status?filter={"ids":["test"]}');
        assert.equal(resp.statusCode, 200);
        assert.deepEqual(resp.body, { mongo: { connected: true } });
    });

    it('non-exist path', async () => {
        const resp = await supertest(services.app).get('/random').set(ORCHESTY_API_KEY, 'abc');
        assert.equal(resp.statusCode, 401);
        assert.deepEqual(resp.body, { message: 'Bad scopes' });
    });

    it('bad credentials', async () => {
        const resp = await supertest(services.app).get('/metrics/monolith').set(ORCHESTY_API_KEY, 'abc');
        assert.equal(resp.statusCode, 401);
        assert.deepEqual(resp.body, { message: 'Bad credentials' });
    });
});
