import assert from 'assert';
import { Express } from 'express';
import { InsertManyResult } from 'mongodb';
import supertest from 'supertest';
import { createBillingApiData, getBillingApiData } from '../../../test/dataProvider';
import Services from '../../DIContainer/Services';
import Cloud from '../../entities/Cloud';
import { CloudPlan } from '../../enums/CloudPlan';
import { CollectionEnum } from '../../enums/CollectionEnum';
import { Period } from '../../enums/Period';
import { container } from '../../index';

const basicData = {
    created: null,
    updated: null,
    deleted: null,
    tenantId: 'tenantId',
    plan: CloudPlan.BASIC,
    price: 0,
    period: Period.MONTHLY,
    startDate: new Date('2022-01-01'),
    closeDate: new Date('2022-01-01'),
} as Cloud;

function getServer(): Express {
    return container.get<Express>(Services.SERVER);
}

async function createClouds(data: Cloud = basicData): Promise<InsertManyResult> {
    return createBillingApiData(CollectionEnum.CLOUD, [data]);
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
    describe('create', () => {
        it('ok', async () => {
            const resp = await supertest(getServer()).post('/clouds').send(basicData);
            assert.deepEqual(resp.statusCode, 200);
            const data = JSON.parse(JSON.stringify(basicData));
            data._id = resp.body.cloud._id;
            data.created = resp.body.cloud.created;
            assert.deepEqual(resp.body.cloud, data);
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
    describe('delete', () => {
        it('ok', async () => {
            const insertedId = Object.values((await createClouds(basicData)).insertedIds)[0].toString();
            const resp = await supertest(getServer()).delete(`/clouds/${insertedId}`);
            assert.deepEqual(resp.statusCode, 200);
            assert.deepEqual(resp.body.msg, 'Entity successfully deleted!');
            const entity = await getBillingApiData(CollectionEnum.ADDRESS, insertedId);
            assert.deepEqual(entity, null);
        });
    });
});
