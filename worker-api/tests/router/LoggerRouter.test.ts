import assert from 'assert';
import supertest from 'supertest';
import { init, IServices } from '../../src';
import { ORCHESTY_API_KEY } from '../../src/authorization/AuthorizationMiddleware';
import { ScopeEnum } from '../../src/authorization/ScopeEnum';
import { mongoOptions } from '../../src/config/Config';

let services: IServices;

describe('Tests for logs router', () => {
    beforeAll(async () => {
        services = await init();
    });

    beforeEach(async () => {
        await services.mongo.dropCollections();
    });

    afterAll(async () => {
        await services.loki.close();
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
            isForUi: true,
        });
        assert.equal(resp.statusCode, 200);
        assert.deepEqual(resp.body, { message: { data: '', status: 'OK' } });

        const logs = await services.mongo.getCollection(mongoOptions.logsCollection).find().toArray();
        assert.equal(logs.length, 1);
        assert.equal(logs[0].message, 'failed');
        assert.equal(logs[0].pipes?.service, 'sdk');
    });

    it('logs - not for ui is dropped', async () => {
        const key = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyLCJzY29wZXMiOlsibWV0cmljOndyaXRlIl19.Er9Nioiq77-sahV5XOoZuFBfIbBEgXV45BfdRsbXWdQ';

        const apiKeyCollection = services.mongo.getApiKeyCollection();
        await apiKeyCollection.insertOne({ key, scopes: [ScopeEnum.LOG_WRITE] });

        const resp = await supertest(services.app).post('/logger/logs').set(ORCHESTY_API_KEY, key).send({
            timestamp: 1681225378307,
            service: 'sdk',
            message: 'failed',
            isForUi: false,
        });

        assert.equal(resp.statusCode, 200);
        assert.deepEqual(resp.body, { message: { data: '', status: 'OK' } });

        const logs2 = await services.mongo.getCollection(mongoOptions.logsCollection).find().toArray();
        assert.equal(logs2.length, 0);
    });

    it('loki - big payload is accepted', async () => {
        const key = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyLCJzY29wZXMiOlsibWV0cmljOndyaXRlIl19.Er9Nioiq77-sahV5XOoZuFBfIbBEgXV45BfdRsbXWdQ';

        const apiKeyCollection = services.mongo.getApiKeyCollection();
        await apiKeyCollection.insertOne({ key, scopes: [ScopeEnum.LOG_WRITE] });

        const resp = await supertest(services.app).post('/logger/loki').set(ORCHESTY_API_KEY, key).send({
            timestamp: 1681225378307,
            service: 'sdk',
            message: 'Incoming request',
            data: 'x'.repeat(1_500 * 1_024),
        });

        assert.equal(resp.statusCode, 200);
        assert.deepEqual(resp.body, { message: { status: 'OK' } });
    });

    it('loki - big payload is not read when unauthorized', async () => {
        const resp = await supertest(services.app).post('/logger/loki').send({
            timestamp: 1681225378307,
            service: 'sdk',
            message: 'Incoming request',
            data: 'x'.repeat(2_500 * 1_024),
        });

        assert.equal(resp.statusCode, 401);
    });

    it('loki - payload over the limit is rejected', async () => {
        const key = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyLCJzY29wZXMiOlsibWV0cmljOndyaXRlIl19.Er9Nioiq77-sahV5XOoZuFBfIbBEgXV45BfdRsbXWdQ';

        const apiKeyCollection = services.mongo.getApiKeyCollection();
        await apiKeyCollection.insertOne({ key, scopes: [ScopeEnum.LOG_WRITE] });

        const resp = await supertest(services.app).post('/logger/loki').set(ORCHESTY_API_KEY, key).send({
            timestamp: 1681225378307,
            service: 'sdk',
            message: 'Incoming request',
            data: 'x'.repeat(2_500 * 1_024),
        });

        assert.equal(resp.statusCode, 413);
    });

    it('document - big payload is rejected by the stricter default limit', async () => {
        const key = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyLCJzY29wZXMiOlsid29ya2VyOmFsbCJdfQ.L0I7Yf92rj1uXOikdzl2SN1sXJdfbHpRE8aT_q6I99A';

        const apiKeyCollection = services.mongo.getApiKeyCollection();
        await apiKeyCollection.insertOne({ key, scopes: [ScopeEnum.WORKER_ALL] });

        const resp = await supertest(services.app).post('/document/ApplicationInstall').set(ORCHESTY_API_KEY, key).send({
            name: 'x'.repeat(1_500 * 1_024),
        });

        assert.equal(resp.statusCode, 413);
    });
});
