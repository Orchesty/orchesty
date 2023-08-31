import assert from 'assert';
import supertest from 'supertest';
import { init, IServices } from '../../src';
import { ORCHESTY_API_KEY } from '../../src/authorization/AuthorizationMiddleware';
import { ScopeEnum } from '../../src/authorization/ScopeEnum';

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
            level: 50,
            time: 1681225378307,
            pid: 1,
            hostname: 'pipes-worker-595fc8b6c6-zg882',
            previousNodeId: '64199a06345bee59440cfc34',
            nodeId: '64199a06345bee59440cfc35',
            correlationId: 'b63a69b5-b409-4543-abcb-c2b764c43e5f',
            topologyId: '64199a06cebdd20846060009',
            processId: '39009ff7-2b2f-4ac5-afd1-3c3848fb0aa7',
            parentId: 'a77d8a7f-b78f-4036-a340-9e349223376c',
            sequenceId: '0',
            userId: 'abcd',
            applications: 'test;test2',
            timestamp: 1681225378307,
            service: 'sdk',
            levelName: 'error',
            message: 'failed',
        });
        assert.equal(resp.statusCode, 200);
        assert.deepEqual(resp.body, { message: { data: '', status: 'OK' } });
    });
});
