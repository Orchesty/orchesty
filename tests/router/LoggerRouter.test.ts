import assert from 'assert';
import supertest from 'supertest';
import { init, IServices } from '../../src';
import { ORCHESTY_API_KEY } from '../../src/authorization/AuthorizationMiddleware';
import { ScopeEnum } from '../../src/authorization/ScopeEnum';
import ResultCode from '../../src/enum/ResultCode';

let services: IServices;
describe('Tests for logs router', () => {
    beforeAll(async () => {
        services = await init();
    });

    beforeEach(async () => {
        await services.mongo.dropCollections();
    });

    afterAll(async () => {
        await services.mongo.disconnect();
    });

    it('logs - empty headers (unauthorized)', async () => {
        const resp = await supertest(services.app).post('/logger/logs');
        assert.equal(resp.statusCode, 401);
    });

    it('logs - not valid data', async () => {
        const key = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyLCJzY29wZXMiOlsibWV0cmljOndyaXRlIl19.Er9Nioiq77-sahV5XOoZuFBfIbBEgXV45BfdRsbXWdQ';

        const apiKeyCollection = services.mongo.getApiKeyCollection();
        await apiKeyCollection.insertOne({ key, scopes: [ScopeEnum.LOG_WRITE] });

        const resp = await supertest(services.app).post('/logger/logs').set(ORCHESTY_API_KEY, key);
        assert.equal(resp.statusCode, 400);
        assert.deepEqual(resp.body, { message: { error: '"value" is required' } });
    });

    it('logs - ok', async () => {
        const key = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyLCJzY29wZXMiOlsibWV0cmljOndyaXRlIl19.Er9Nioiq77-sahV5XOoZuFBfIbBEgXV45BfdRsbXWdQ';

        const apiKeyCollection = services.mongo.getApiKeyCollection();
        await apiKeyCollection.insertOne({ key, scopes: [ScopeEnum.LOG_WRITE] });

        const resp = await supertest(services.app).post('/logger/logs').set(ORCHESTY_API_KEY, key).send({
            timestamp: 123,
            hostname: 'testHostName',
            service: 'testType',
            level: 'testSeverity',
            message: 'testMessage',
            resultCode: ResultCode.SUCCESS,
            isForUi: true,
        });
        assert.equal(resp.statusCode, 200);
        assert.deepEqual(resp.body, { message: { data: '', status: 'OK' } });
    });
});
