import assert from 'assert';
import { Express } from 'express';
import { InsertManyResult } from 'mongodb';
import supertest from 'supertest';
import { createBillingApiData, getBillingApiData } from '../../../test/dataProvider';
import Address from '../../admin/entities/Address';
import Services from '../../base/DIContainer/Services';
import { CollectionEnum } from '../../base/enums/CollectionEnum';
import { container } from '../../index';

const basicData = {
    created: null,
    updated: null,
    deleted: null,
    url: 'url',
    email: 'email',
    title: 'title',
    city: 'city',
    street: 'street',
    isSendReminder: false,
    isRegisteredForVatOnPay: false,
    identificationNumber: 'identificationNumber',
    defaultInvoiceMaturity: 20,
    countryId: 'countryId',
    companyName: 'companyName',
    postalCode: 'postalCode',
    surname: 'surname',
    phone: 'phone',
    tenantId: 'tenantId',
    firstname: 'firstname',
    vatIdentificationNumber: 'vatIdentificationNumber',
} as Address;

function getServer(): Express {
    return container.get<Express>(Services.SERVER);
}

async function createAddresses(data: Address = basicData): Promise<InsertManyResult> {
    return createBillingApiData(CollectionEnum.ADDRESS, [data]);
}

describe('addressesController', () => {
    describe('list', () => {
        it('ok', async () => {
            await createAddresses();
            const resp = await supertest(getServer()).get('/addresses').query({});
            assert.deepEqual(resp.statusCode, 200);
            assert.deepEqual(resp.body.rows.length, 1);
            const data = JSON.parse(JSON.stringify(basicData));
            data._id = resp.body.rows[0]._id;
            assert.deepEqual(resp.body.rows, [data]);
        });
        it('deleted', async () => {
            const data = JSON.parse(JSON.stringify(basicData));
            data.deleted = new Date();
            await createAddresses(data);
            const resp = await supertest(getServer()).get('/addresses').query({});
            assert.deepEqual(resp.statusCode, 200);
            assert.deepEqual(resp.body.rows.length, 0);
        });
    });
    describe('create', () => {
        it('ok', async () => {
            const resp = await supertest(getServer()).post('/addresses').send(basicData);
            assert.deepEqual(resp.statusCode, 200);
            const data = JSON.parse(JSON.stringify(basicData));
            data._id = resp.body.address._id;
            data.created = resp.body.address.created;
            assert.deepEqual(resp.body.address, data);
        });
    });
    describe('get', () => {
        it('ok', async () => {
            const insertedId = Object.values((await createAddresses(basicData)).insertedIds)[0].toString();
            const resp = await supertest(getServer()).get(`/addresses/${insertedId}`);
            const data = JSON.parse(JSON.stringify(basicData));
            data._id = insertedId;
            assert.deepEqual(resp.statusCode, 200);
            assert.deepEqual(resp.body.address, data);
        });
        it('400', async () => {
            await createAddresses();
            const resp = await supertest(getServer()).get('/addresses/123456789012');
            assert.deepEqual(resp.statusCode, 400);
        });
    });
    describe('update', () => {
        it('ok', async () => {
            const insertedId = Object.values((await createAddresses(basicData)).insertedIds)[0].toString();
            const resp = await supertest(getServer()).put(`/addresses/${insertedId}`).send({ tenantId: 'tenantId1' });
            const data = JSON.parse(JSON.stringify(basicData));
            data._id = insertedId;
            data.updated = resp.body.address.updated;
            data.tenantId = 'tenantId1';
            assert.deepEqual(resp.statusCode, 200);
            assert.deepEqual(resp.body.address, data);
        });
        it('400', async () => {
            await createAddresses();
            const resp = await supertest(getServer()).put('/addresses/123456789012').send({ tenantId: 'tenantId1' });
            assert.deepEqual(resp.statusCode, 400);
        });
    });
    describe('delete', () => {
        it('ok', async () => {
            const insertedId = Object.values((await createAddresses(basicData)).insertedIds)[0].toString();
            const resp = await supertest(getServer()).delete(`/addresses/${insertedId}`);
            assert.deepEqual(resp.statusCode, 200);
            assert.deepEqual(resp.body.msg, 'Entity successfully deleted!');
            const entity = await getBillingApiData(CollectionEnum.ADDRESS, insertedId);
            assert.deepEqual(entity, null);
        });
    });
});
