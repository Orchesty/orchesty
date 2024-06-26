import assert from 'assert';
import { Express } from 'express';
import { InsertManyResult } from 'mongodb';
import supertest from 'supertest';
import { createBillingApiData, getBillingApiData } from '../../../test/dataProvider';
import Correction from '../../admin/entities/Correction';
import Services from '../../base/DIContainer/Services';
import { CollectionEnum } from '../../base/enums/CollectionEnum';
import { container } from '../../index';

const basicData = {
    created: null,
    updated: null,
    deleted: null,
    tenantId: 'tenantId',
    date: new Date('2022-01-01'),
    hours: 0,
    amount: 0,
    note: 'note',
} as Correction;

function getServer(): Express {
    return container.get<Express>(Services.SERVER);
}

async function createCorrections(data: Correction = basicData): Promise<InsertManyResult> {
    return createBillingApiData(CollectionEnum.CORRECTION, [data]);
}

describe('correctionsController', () => {
    describe('list', () => {
        it('ok', async () => {
            await createCorrections();
            const resp = await supertest(getServer()).get('/corrections').query({});
            assert.deepEqual(resp.statusCode, 200);
            assert.deepEqual(resp.body.rows.length, 1);
            const data = JSON.parse(JSON.stringify(basicData));
            data._id = resp.body.rows[0]._id;
            assert.deepEqual(resp.body.rows, [data]);
        });
        it('deleted', async () => {
            const data = JSON.parse(JSON.stringify(basicData));
            data.deleted = new Date();
            await createCorrections(data);
            const resp = await supertest(getServer()).get('/corrections').query({});
            assert.deepEqual(resp.statusCode, 200);
            assert.deepEqual(resp.body.rows.length, 0);
        });
    });
    describe('create', () => {
        it('ok', async () => {
            const resp = await supertest(getServer()).post('/corrections').send(basicData);
            assert.deepEqual(resp.statusCode, 200);
            const data = JSON.parse(JSON.stringify(basicData));
            data._id = resp.body.correction._id;
            data.created = resp.body.correction.created;
            assert.deepEqual(resp.body.correction, data);
        });
    });
    describe('get', () => {
        it('ok', async () => {
            const insertedId = Object.values((await createCorrections(basicData)).insertedIds)[0].toString();
            const resp = await supertest(getServer()).get(`/corrections/${insertedId}`);
            const data = JSON.parse(JSON.stringify(basicData));
            data._id = insertedId;
            assert.deepEqual(resp.statusCode, 200);
            assert.deepEqual(resp.body.correction, data);
        });
        it('400', async () => {
            await createCorrections();
            const resp = await supertest(getServer()).get('/corrections/123456789012123456789012');
            assert.deepEqual(resp.statusCode, 400);
        });
    });
    describe('update', () => {
        it('ok', async () => {
            const insertedId = Object.values((await createCorrections(basicData)).insertedIds)[0].toString();
            const resp = await supertest(getServer()).put(`/corrections/${insertedId}`).send({ tenantId: 'tenantId1' });
            const data = JSON.parse(JSON.stringify(basicData));
            data._id = insertedId;
            data.updated = resp.body.correction.updated;
            data.tenantId = 'tenantId1';
            assert.deepEqual(resp.statusCode, 200);
            assert.deepEqual(resp.body.correction, data);
        });
        it('400', async () => {
            await createCorrections();
            const resp = await supertest(getServer()).put('/corrections/123456789012123456789012').send({ tenantId: 'tenantId1' });
            assert.deepEqual(resp.statusCode, 400);
        });
    });
    describe('delete', () => {
        it('ok', async () => {
            const insertedId = Object.values((await createCorrections(basicData)).insertedIds)[0].toString();
            const resp = await supertest(getServer()).delete(`/corrections/${insertedId}`);
            assert.deepEqual(resp.statusCode, 200);
            assert.deepEqual(resp.body.msg, 'Entity successfully deleted!');
            const entity = await getBillingApiData(CollectionEnum.CORRECTION, insertedId);
            assert.deepEqual(entity, null);
        });
    });
});
