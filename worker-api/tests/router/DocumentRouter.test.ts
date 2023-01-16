import assert from 'assert';
import supertest from 'supertest';
import { init, IServices } from '../../src';
import { ORCHESTY_API_KEY } from '../../src/authorization/AuthorizationMiddleware';
import { ScopeEnum } from '../../src/authorization/ScopeEnum';
import DocumentEnum from '../../src/enum/DocumentEnum';

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

    it('document - empty headers (unauthorized)', async () => {
        const resp = await supertest(services.app).post('/document/AppInstall');
        assert.equal(resp.statusCode, 401);
    });

    it('document - save: unsupported document', async () => {
        const key = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyLCJzY29wZXMiOlsid29ya2VyOmFsbCJdfQ.L0I7Yf92rj1uXOikdzl2SN1sXJdfbHpRE8aT_q6I99A';

        const apiKeyCollection = services.mongo.getApiKeyCollection();
        await apiKeyCollection.insertOne({ key, scopes: [ScopeEnum.WORKER_ALL] });

        const resp = await supertest(services.app).post('/document/CustomDocument').set(ORCHESTY_API_KEY, key);
        assert.equal(resp.statusCode, 400);
        assert.deepEqual(resp.body, { message: { error: 'Unsupported document [CustomDocument]' } });
    });

    it('document - get: unsupported document', async () => {
        const key = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyLCJzY29wZXMiOlsid29ya2VyOmFsbCJdfQ.L0I7Yf92rj1uXOikdzl2SN1sXJdfbHpRE8aT_q6I99A';

        const apiKeyCollection = services.mongo.getApiKeyCollection();
        await apiKeyCollection.insertOne({ key, scopes: [ScopeEnum.WORKER_ALL] });

        const resp = await supertest(services.app).get('/document/CustomDocument').set(ORCHESTY_API_KEY, key);
        assert.equal(resp.statusCode, 400);
        assert.deepEqual(resp.body, { message: { error: 'Unsupported document [CustomDocument]' } });
    });

    it('document - delete: unsupported document', async () => {
        const key = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyLCJzY29wZXMiOlsid29ya2VyOmFsbCJdfQ.L0I7Yf92rj1uXOikdzl2SN1sXJdfbHpRE8aT_q6I99A';

        const apiKeyCollection = services.mongo.getApiKeyCollection();
        await apiKeyCollection.insertOne({ key, scopes: [ScopeEnum.WORKER_ALL] });

        const resp = await supertest(services.app).delete('/document/CustomDocument').set(ORCHESTY_API_KEY, key);
        assert.equal(resp.statusCode, 400);
        assert.deepEqual(resp.body, { message: { error: 'Unsupported document [CustomDocument]' } });
    });

    it('document - get', async () => {
        const key = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyLCJzY29wZXMiOlsid29ya2VyOmFsbCJdfQ.L0I7Yf92rj1uXOikdzl2SN1sXJdfbHpRE8aT_q6I99A';

        const apiKeyCollection = services.mongo.getApiKeyCollection();
        await apiKeyCollection.insertOne({ key, scopes: [ScopeEnum.WORKER_ALL] });

        const documentCollection = services.mongo.getCollection(DocumentEnum.APPLICATION_INSTALL);
        await documentCollection.insertOne(
            {
                key: 'testKey',
                user: 'testUser',
                enabled: true,
                expires: new Date(2022, 1, 2),
                nonEncrypt: { test: 'testValue' },
                deleted: null,
            },
        );

        const resp = await supertest(services.app).get('/document/ApplicationInstall').set(ORCHESTY_API_KEY, key).send();
        assert.equal(resp.statusCode, 200);
        const { body } = resp;
        assert.deepEqual(
            resp.body,
            [
                {
                    _id: body[0]._id,
                    enabled: true,
                    expires: '2022-02-02T00:00:00.000Z',
                    key: 'testKey',
                    nonEncrypt: { test: 'testValue' },
                    user: 'testUser',
                    deleted: null,
                },
            ],
        );

        const resp1 = await supertest(services.app).get('/document/ApplicationInstall?filter={"ids":["507f1f77bcf86cd799439011"]}').set(ORCHESTY_API_KEY, key).send();
        assert.equal(resp1.statusCode, 200);
        assert.deepEqual(resp1.body, []);

        const resp2 = await supertest(services.app).get('/document/ApplicationInstall?filter={"users":["testUser"]}').set(ORCHESTY_API_KEY, key).send();
        assert.equal(resp2.statusCode, 200);
        assert.deepEqual(
            resp2.body,
            [
                {
                    _id: resp2.body[0]._id,
                    enabled: true,
                    expires: '2022-02-02T00:00:00.000Z',
                    key: 'testKey',
                    nonEncrypt: { test: 'testValue' },
                    user: 'testUser',
                    deleted: null,
                },
            ],
        );

        const resp3 = await supertest(services.app).get('/document/ApplicationInstall?filter={"users":["1"]}').set(ORCHESTY_API_KEY, key).send();
        assert.equal(resp3.statusCode, 200);
        assert.deepEqual(resp3.body, []);

        const resp4 = await supertest(services.app).get('/document/ApplicationInstall?filter={"names":["testKey"]}').set(ORCHESTY_API_KEY, key).send();
        assert.equal(resp4.statusCode, 200);
        assert.deepEqual(
            resp4.body,
            [
                {
                    _id: resp2.body[0]._id,
                    enabled: true,
                    expires: '2022-02-02T00:00:00.000Z',
                    key: 'testKey',
                    nonEncrypt: { test: 'testValue' },
                    user: 'testUser',
                    deleted: null,
                },
            ],
        );
    });

    it('document - insert/get/delete', async () => {
        const key = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyLCJzY29wZXMiOlsid29ya2VyOmFsbCJdfQ.L0I7Yf92rj1uXOikdzl2SN1sXJdfbHpRE8aT_q6I99A';

        const apiKeyCollection = services.mongo.getApiKeyCollection();
        await apiKeyCollection.insertOne({ key, scopes: [ScopeEnum.WORKER_ALL] });

        const resp = await supertest(services.app).post('/document/ApplicationInstall').set(ORCHESTY_API_KEY, key).send({
            key: 'testKey',
            user: 'testUser',
            enabled: true,
            expires: new Date(2022, 1, 2),
            nonEncrypt: { test: 'testValue' },
            deleted: null,
        });
        assert.equal(resp.statusCode, 200);
        assert.deepEqual(resp.body, { message: { status: 'OK', data: '' } });

        const resp1 = await supertest(services.app).get('/document/ApplicationInstall').set(ORCHESTY_API_KEY, key).send();
        assert.equal(resp1.statusCode, 200);
        assert.deepEqual(
            resp1.body,
            [
                { _id: resp1.body[0]._id,
                    enabled: true,
                    expires: '2022-02-02T00:00:00.000Z',
                    key: 'testKey',
                    nonEncrypt: { test: 'testValue' },
                    user: 'testUser',
                    deleted: null,
                },
            ],
        );

        const resp2 = await supertest(services.app).delete('/document/ApplicationInstall').set(ORCHESTY_API_KEY, key).send();
        assert.equal(resp2.statusCode, 200);
        assert.deepEqual(resp2.body, { message: { error: 'Empty filter is not supported.' } });

        const resp3 = await supertest(services.app).delete('/document/ApplicationInstall?filter={"enabled":true}').set(ORCHESTY_API_KEY, key).send();
        assert.equal(resp3.statusCode, 200);
        assert.deepEqual(resp3.body, { message: { status: 'OK', data: { deleted: 1 } } });
    });
});
