import assert from 'assert';
import { Express } from 'express';
import { InsertManyResult } from 'mongodb';
import supertest from 'supertest';
import { createBillingApiData, getBillingApiData } from '../../../test/dataProvider';
import Module from '../../admin/entities/Module';
import Services from '../../base/DIContainer/Services';
import { CollectionEnum } from '../../base/enums/CollectionEnum';
import { container } from '../../index';

const basicData = {
    created: null,
    updated: null,
    deleted: null,
    appName: 'appName',
    applinthId: 'applinthId',
    price: 0,
    minPrice: 0,
    minPriceDate: new Date('2022-01-01'),
} as Module;

function getServer(): Express {
    return container.get<Express>(Services.SERVER);
}

async function createModules(data: Module = basicData): Promise<InsertManyResult> {
    return createBillingApiData(CollectionEnum.MODULE, [data]);
}

describe('modulesController', () => {
    describe('list', () => {
        it('ok', async () => {
            await createModules();
            const resp = await supertest(getServer()).get('/modules').query({});
            assert.deepEqual(resp.statusCode, 200);
            assert.deepEqual(resp.body.rows.length, 1);
            const data = JSON.parse(JSON.stringify(basicData));
            data._id = resp.body.rows[0]._id;
            assert.deepEqual(resp.body.rows, [data]);
        });
        it('deleted', async () => {
            const data = JSON.parse(JSON.stringify(basicData));
            data.deleted = new Date();
            await createModules(data);
            const resp = await supertest(getServer()).get('/modules').query({});
            assert.deepEqual(resp.statusCode, 200);
            assert.deepEqual(resp.body.rows.length, 0);
        });
    });
    describe('create', () => {
        it('ok', async () => {
            const resp = await supertest(getServer()).post('/modules').send(basicData);
            assert.deepEqual(resp.statusCode, 200);
            const data = JSON.parse(JSON.stringify(basicData));
            data._id = resp.body.module._id;
            data.created = resp.body.module.created;
            assert.deepEqual(resp.body.module, data);
        });
    });
    describe('get', () => {
        it('ok', async () => {
            const insertedId = Object.values((await createModules(basicData)).insertedIds)[0].toString();
            const resp = await supertest(getServer()).get(`/modules/${insertedId}`);
            const data = JSON.parse(JSON.stringify(basicData));
            data._id = insertedId;
            assert.deepEqual(resp.statusCode, 200);
            assert.deepEqual(resp.body.module, data);
        });
        it('400', async () => {
            await createModules();
            const resp = await supertest(getServer()).get('/modules/123456789012123456789012');
            assert.deepEqual(resp.statusCode, 400);
        });
    });
    describe('update', () => {
        it('ok', async () => {
            const insertedId = Object.values((await createModules(basicData)).insertedIds)[0].toString();
            const resp = await supertest(getServer()).put(`/modules/${insertedId}`).send({ appName: 'appName1' });
            const data = JSON.parse(JSON.stringify(basicData));
            data._id = insertedId;
            data.updated = resp.body.module.updated;
            data.appName = 'appName1';
            assert.deepEqual(resp.statusCode, 200);
            assert.deepEqual(resp.body.module, data);
        });
        it('400', async () => {
            await createModules();
            const resp = await supertest(getServer()).put('/modules/123456789012123456789012').send({ clientId: 'clientId1' });
            assert.deepEqual(resp.statusCode, 400);
        });
    });
    describe('delete', () => {
        it('ok', async () => {
            const insertedId = Object.values((await createModules(basicData)).insertedIds)[0].toString();
            const resp = await supertest(getServer()).delete(`/modules/${insertedId}`);
            assert.deepEqual(resp.statusCode, 200);
            assert.deepEqual(resp.body.msg, 'Entity successfully deleted!');
            const entity = await getBillingApiData(CollectionEnum.MODULE, insertedId);
            assert.deepEqual(entity, null);
        });
    });
});
