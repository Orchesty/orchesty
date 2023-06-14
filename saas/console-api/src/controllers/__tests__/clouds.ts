import assert from 'assert';
import { Express } from 'express';
import supertest from 'supertest';
import { cloudBasicData as basicData, createClouds } from '../../../test/dataProvider';
import Services from '../../base/DIContainer/Services';
import { container } from '../../index';

function getServer(): Express {
    return container.get<Express>(Services.SERVER);
}

describe('cloudsController', () => {
    describe('list', () => {
        it('ok', async () => {
            await createClouds();
            const resp = await supertest(getServer()).get('/clouds').query({});
            assert.deepEqual(resp.statusCode, 200);
            assert.deepEqual(resp.body.rows.length, 1);
            const data = JSON.parse(JSON.stringify(basicData));
            data._id = resp.body.rows[0]._id;
            assert.deepEqual(resp.body.rows, [data]);
        });
        it('deleted', async () => {
            const data = JSON.parse(JSON.stringify(basicData));
            data.deleted = new Date();
            await createClouds(data);
            const resp = await supertest(getServer()).get('/clouds').query({});
            assert.deepEqual(resp.statusCode, 200);
            assert.deepEqual(resp.body.rows.length, 0);
        });
    });
    describe('get', () => {
        it('ok', async () => {
            const insertedId = Object.values((await createClouds(basicData)).insertedIds)[0].toString();
            const resp = await supertest(getServer()).get(`/clouds/${insertedId}`);
            const data = JSON.parse(JSON.stringify(basicData));
            data._id = insertedId;
            assert.deepEqual(resp.statusCode, 200);
            assert.deepEqual(resp.body.cloud, data);
        });
        it('400', async () => {
            await createClouds();
            const resp = await supertest(getServer()).get('/clouds/123456789012');
            assert.deepEqual(resp.statusCode, 400);
        });
    });
    describe('update', () => {
        it('ok', async () => {
            const insertedId = Object.values((await createClouds(basicData)).insertedIds)[0].toString();
            const resp = await supertest(getServer()).put(`/clouds/${insertedId}`).send({ tenantId: 'tenantId1' });
            const data = JSON.parse(JSON.stringify(basicData));
            data._id = insertedId;
            data.tenantId = 'tenantId1';
            data.updated = resp.body.cloud.updated;
            assert.deepEqual(resp.statusCode, 200);
            assert.deepEqual(resp.body.cloud, data);
        });
        it('400', async () => {
            await createClouds();
            const resp = await supertest(getServer()).put('/clouds/123456789012').send({ tenantId: 'tenantId1' });
            assert.deepEqual(resp.statusCode, 400);
        });
    });
});
