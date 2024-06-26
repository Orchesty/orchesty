import assert from 'assert';
import { Express } from 'express';
import { InsertManyResult } from 'mongodb';
import supertest from 'supertest';
import { createBillingApiData, getBillingApiData } from '../../../test/dataProvider';
import Client from '../../admin/entities/Client';
import Services from '../../base/DIContainer/Services';
import { CollectionEnum } from '../../base/enums/CollectionEnum';
import { container } from '../../index';

const basicData = {
    created: null,
    updated: null,
    deleted: null,
    companyName: 'companyName',
    contact: [
        {
            name: 'name',
            email: 'email',
            phone: 'phone',
        },
    ],
    supportHourlyRate: 0,
    supportSubscription: 0,
    supportResponseTime: 0,
    invoicingId: 'invoicingId',
    hourlyRate: 0,
    note: 'note',
} as Client;

function getServer(): Express {
    return container.get<Express>(Services.SERVER);
}

async function createClients(data: Client = basicData): Promise<InsertManyResult> {
    return createBillingApiData(CollectionEnum.CLIENT, [data]);
}

describe('clientsController', () => {
    describe('list', () => {
        it('ok', async () => {
            await createClients();
            const resp = await supertest(getServer()).get('/clients').query({});
            assert.deepEqual(resp.statusCode, 200);
            assert.deepEqual(resp.body.rows.length, 1);
            const data = JSON.parse(JSON.stringify(basicData));
            data._id = resp.body.rows[0]._id;
            assert.deepEqual(resp.body.rows, [data]);
        });
        it('deleted', async () => {
            const data = JSON.parse(JSON.stringify(basicData));
            data.deleted = new Date();
            await createClients(data);
            const resp = await supertest(getServer()).get('/clients').query({});
            assert.deepEqual(resp.statusCode, 200);
            assert.deepEqual(resp.body.rows.length, 0);
        });
    });
    describe('create', () => {
        it('ok', async () => {
            const resp = await supertest(getServer()).post('/clients').send(basicData);
            assert.deepEqual(resp.statusCode, 200);
            const data = JSON.parse(JSON.stringify(basicData));
            data._id = resp.body.client._id;
            data.created = resp.body.client.created;
            assert.deepEqual(resp.body.client, data);
        });
    });
    describe('get', () => {
        it('ok', async () => {
            const insertedId = Object.values((await createClients(basicData)).insertedIds)[0].toString();
            const resp = await supertest(getServer()).get(`/clients/${insertedId}`);
            const data = JSON.parse(JSON.stringify(basicData));
            data._id = insertedId;
            assert.deepEqual(resp.statusCode, 200);
            assert.deepEqual(resp.body.client, data);
        });
        it('400', async () => {
            await createClients();
            const resp = await supertest(getServer()).get('/clients/123456789012123456789012');
            assert.deepEqual(resp.statusCode, 400);
        });
    });
    describe('update', () => {
        it('ok', async () => {
            const insertedId = Object.values((await createClients(basicData)).insertedIds)[0].toString();
            const resp = await supertest(getServer()).put(`/clients/${insertedId}`).send({ companyName: 'companyName1' });
            const data = JSON.parse(JSON.stringify(basicData));
            data._id = insertedId;
            data.updated = resp.body.client.updated;
            data.companyName = 'companyName1';
            assert.deepEqual(resp.statusCode, 200);
            assert.deepEqual(resp.body.client, data);
        });
        it('400', async () => {
            await createClients();
            const resp = await supertest(getServer()).put('/clients/123456789012123456789012').send({ clientId: 'clientId1' });
            assert.deepEqual(resp.statusCode, 400);
        });
    });
    describe('delete', () => {
        it('ok', async () => {
            const insertedId = Object.values((await createClients(basicData)).insertedIds)[0].toString();
            const resp = await supertest(getServer()).delete(`/clients/${insertedId}`);
            assert.deepEqual(resp.statusCode, 200);
            assert.deepEqual(resp.body.msg, 'Entity successfully deleted!');
            const entity = await getBillingApiData(CollectionEnum.CLIENT, insertedId);
            assert.deepEqual(entity, null);
        });
    });
});
