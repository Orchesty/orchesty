import assert from 'assert';
import { Application } from 'express';
import supertest from 'supertest';
import { init } from '../../src';
import { ORCHESTY_API_KEY } from '../../src/authorization/AuthorizationMiddleware';
import Mongo from '../../src/database/Mongo';

let mongo: Mongo;
let expressApp: Application;
describe('Tests for TestBatch', () => {
    beforeAll(async () => {
        expressApp = await init();
    });

    beforeEach(async () => {
        mongo = new Mongo();
        await mongo.connect();
        await mongo.dropCollections();
    });

    afterEach(async () => {
        await mongo.disconnect();
    });

    it('isAuthorized - empty headers', async () => {
        const resp = await supertest(expressApp).get('/status');
        assert.equal(resp.statusCode, 200);
        assert.deepEqual(resp.body, { mongo: { connected: true } });
    });

    it('isAuthorized - no mongo apiKey', async () => {
        const resp = await supertest(expressApp).get('/status').set(ORCHESTY_API_KEY, 'test');
        assert.equal(resp.statusCode, 200);
        assert.deepEqual(resp.body, { mongo: { connected: true } });
    });

    it('isAuthorized - bad credentials', async () => {
        const apiKeyCollection = mongo.getApiKeyCollection();
        await apiKeyCollection.insertOne({ apiKey: 'otherTest' });

        const resp = await supertest(expressApp).get('/status').set(ORCHESTY_API_KEY, 'test');
        assert.equal(resp.statusCode, 200);
        assert.deepEqual(resp.body, { mongo: { connected: true } });
    });

    it('isAuthorized - ok', async () => {
        const apiKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyLCJzY29wZXMiOlsibWV0cmljOndyaXRlIl19.Er9Nioiq77-sahV5XOoZuFBfIbBEgXV45BfdRsbXWdQ';

        const apiKeyCollection = mongo.getApiKeyCollection();
        await apiKeyCollection.insertOne({ apiKey });

        const resp = await supertest(expressApp).get('/status').set(ORCHESTY_API_KEY, apiKey);
        assert.equal(resp.statusCode, 200);
        assert.deepEqual(resp.body, { mongo: { connected: true } });
    });
});
