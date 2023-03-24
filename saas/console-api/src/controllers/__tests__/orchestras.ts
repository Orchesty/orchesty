import assert from 'assert';
import { Express } from 'express';
import { InsertManyResult } from 'mongodb';
import supertest from 'supertest';
import { createBillingApiData, getBillingApiData } from '../../../test/dataProvider';
import Services from '../../DIContainer/Services';
import Orchesty from '../../entities/Orchesty';
import { CollectionEnum } from '../../enums/CollectionEnum';
import { OrchestyVersion } from '../../enums/OrchestyVersion';
import { container } from '../../index';

const basicData = {
    created: null,
    updated: null,
    deleted: null,
    tenantId: 'tenantId',
    instanceId: 'instanceId',
    version: OrchestyVersion.HOSTED,
    price: 0,
    startDate: new Date('2022-01-01'),
} as Orchesty;

function getServer(): Express {
    return container.get<Express>(Services.SERVER);
}

async function createOrchestras(data: Orchesty = basicData): Promise<InsertManyResult> {
    return createBillingApiData(CollectionEnum.ORCHESTY, [data]);
}

describe('orchestrasController', () => {
    describe('list', () => {
        it('ok', async () => {
            await createOrchestras();
            const resp = await supertest(getServer()).get('/orchestras').query({});
            assert.deepEqual(resp.statusCode, 200);
            assert.deepEqual(resp.body.rows.length, 1);
            const data = JSON.parse(JSON.stringify(basicData));
            data._id = resp.body.rows[0]._id;
            assert.deepEqual(resp.body.rows, [data]);
        });
        it('deleted', async () => {
            const data = JSON.parse(JSON.stringify(basicData));
            data.deleted = new Date();
            await createOrchestras(data);
            const resp = await supertest(getServer()).get('/orchestras').query({});
            assert.deepEqual(resp.statusCode, 200);
            assert.deepEqual(resp.body.rows.length, 0);
        });
    });
    describe('create', () => {
        it('ok', async () => {
            const resp = await supertest(getServer()).post('/orchestras').send(basicData);
            assert.deepEqual(resp.statusCode, 200);
            const data = JSON.parse(JSON.stringify(basicData));
            data._id = resp.body.orchesty._id;
            data.created = resp.body.orchesty.created;
            assert.deepEqual(resp.body.orchesty, data);
        });
    });
    describe('get', () => {
        it('ok', async () => {
            const insertedId = Object.values((await createOrchestras(basicData)).insertedIds)[0].toString();
            const resp = await supertest(getServer()).get(`/orchestras/${insertedId}`);
            const data = JSON.parse(JSON.stringify(basicData));
            data._id = insertedId;
            assert.deepEqual(resp.statusCode, 200);
            assert.deepEqual(resp.body.orchesty, data);
        });
        it('400', async () => {
            await createOrchestras();
            const resp = await supertest(getServer()).get('/orchestras/123456789012');
            assert.deepEqual(resp.statusCode, 400);
        });
    });
    describe('update', () => {
        it('ok', async () => {
            const insertedId = Object.values((await createOrchestras(basicData)).insertedIds)[0].toString();
            const resp = await supertest(getServer()).put(`/orchestras/${insertedId}`).send({ tenantId: 'tenantId1' });
            const data = JSON.parse(JSON.stringify(basicData));
            data._id = insertedId;
            data.updated = resp.body.orchesty.updated;
            data.tenantId = 'tenantId1';
            assert.deepEqual(resp.statusCode, 200);
            assert.deepEqual(resp.body.orchesty, data);
        });
        it('400', async () => {
            await createOrchestras();
            const resp = await supertest(getServer()).put('/orchestras/123456789012').send({ tenantId: 'tenantId1' });
            assert.deepEqual(resp.statusCode, 400);
        });
    });
    describe('delete', () => {
        it('ok', async () => {
            const insertedId = Object.values((await createOrchestras(basicData)).insertedIds)[0].toString();
            const resp = await supertest(getServer()).delete(`/orchestras/${insertedId}`);
            assert.deepEqual(resp.statusCode, 200);
            assert.deepEqual(resp.body.msg, 'Entity successfully deleted!');
            const entity = await getBillingApiData(CollectionEnum.ADDRESS, insertedId);
            assert.deepEqual(entity, null);
        });
    });
});
