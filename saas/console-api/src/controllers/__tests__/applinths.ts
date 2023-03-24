import assert from 'assert';
import { Express } from 'express';
import { InsertManyResult } from 'mongodb';
import supertest from 'supertest';
import { createBillingApiData, getBillingApiData } from '../../../test/dataProvider';
import Services from '../../DIContainer/Services';
import Applinth from '../../entities/Applinth';
import { CollectionEnum } from '../../enums/CollectionEnum';
import { container } from '../../index';

const basicData = {
    created: null,
    updated: null,
    deleted: null,
    tenantId: 'tenantId',
    instanceId: 'instanceId',
    minPriceDate: new Date('2022-01-01'),
    minPrice: 0,
} as Applinth;

function getServer(): Express {
    return container.get<Express>(Services.SERVER);
}

async function createApplinths(data: Applinth = basicData): Promise<InsertManyResult> {
    return createBillingApiData(CollectionEnum.APPLINTH, [data]);
}

describe('applinthsController', () => {
    describe('list', () => {
        it('ok', async () => {
            await createApplinths();
            const resp = await supertest(getServer()).get('/applinths').query({});
            assert.deepEqual(resp.statusCode, 200);
            assert.deepEqual(resp.body.rows.length, 1);
            const data = JSON.parse(JSON.stringify(basicData));
            data._id = resp.body.rows[0]._id;
            assert.deepEqual(resp.body.rows, [data]);
        });
        it('deleted', async () => {
            const data = JSON.parse(JSON.stringify(basicData));
            data.deleted = new Date();
            await createApplinths(data);
            const resp = await supertest(getServer()).get('/applinths').query({});
            assert.deepEqual(resp.statusCode, 200);
            assert.deepEqual(resp.body.rows.length, 0);
        });
    });
    describe('create', () => {
        it('ok', async () => {
            const resp = await supertest(getServer()).post('/applinths').send(basicData);
            assert.deepEqual(resp.statusCode, 200);
            const data = JSON.parse(JSON.stringify(basicData));
            data._id = resp.body.applinth._id;
            data.created = resp.body.applinth.created;
            assert.deepEqual(resp.body.applinth, data);
        });
    });
    describe('get', () => {
        it('ok', async () => {
            const insertedId = Object.values((await createApplinths(basicData)).insertedIds)[0].toString();
            const resp = await supertest(getServer()).get(`/applinths/${insertedId}`);
            const data = JSON.parse(JSON.stringify(basicData));
            data._id = insertedId;
            assert.deepEqual(resp.statusCode, 200);
            assert.deepEqual(resp.body.applinth, data);
        });
        it('400', async () => {
            await createApplinths();
            const resp = await supertest(getServer()).get('/applinths/123456789012');
            assert.deepEqual(resp.statusCode, 400);
        });
    });
    describe('update', () => {
        it('ok', async () => {
            const insertedId = Object.values((await createApplinths(basicData)).insertedIds)[0].toString();
            const resp = await supertest(getServer()).put(`/applinths/${insertedId}`).send({ tenantId: 'tenantId1' });
            const data = JSON.parse(JSON.stringify(basicData));
            data._id = insertedId;
            data.tenantId = 'tenantId1';
            data.updated = resp.body.applinth.updated;
            assert.deepEqual(resp.statusCode, 200);
            assert.deepEqual(resp.body.applinth, data);
        });
        it('400', async () => {
            await createApplinths();
            const resp = await supertest(getServer()).put('/applinths/123456789012').send({ tenantId: 'tenantId1' });
            assert.deepEqual(resp.statusCode, 400);
        });
    });
    describe('delete', () => {
        it('ok', async () => {
            const insertedId = Object.values((await createApplinths(basicData)).insertedIds)[0].toString();
            const resp = await supertest(getServer()).delete(`/applinths/${insertedId}`);
            assert.deepEqual(resp.statusCode, 200);
            assert.deepEqual(resp.body.msg, 'Entity successfully deleted!');
            const entity = await getBillingApiData(CollectionEnum.ADDRESS, insertedId);
            assert.deepEqual(entity, null);
        });
    });
});
