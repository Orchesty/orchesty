import assert from 'assert';
import { Express } from 'express';
import { InsertManyResult } from 'mongodb';
import supertest from 'supertest';
import { mockAdapter } from '../../../.jest/testLifecycle';
import { cloudBasicData, createBillingApiData, createClouds, getBillingApiData } from '../../../test/dataProvider';
import Applinth from '../../admin/entities/Applinth';
import Services from '../../base/DIContainer/Services';
import { CollectionEnum } from '../../base/enums/CollectionEnum';
import Mongo from '../../base/storage/mongo/Mongo';
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

function getMongo(): Mongo {
    return container.get<Mongo>(Services.STORAGE);
}

async function createApplinths(data: Applinth = basicData): Promise<InsertManyResult> {
    return createBillingApiData(CollectionEnum.APPLINTH, [data]);
}

describe('applinthsController', () => {
    beforeAll(() => {
        mockAdapter.onPut('http://usscp').reply(200);
    });
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
            const resp = await supertest(getServer()).post('/applinths').send({
                ...basicData,
                cloud: cloudBasicData,
            });
            assert.deepEqual(resp.statusCode, 200);
            const data = JSON.parse(JSON.stringify(basicData));
            const { applinth } = resp.body;
            data._id = applinth._id;
            data.created = applinth.created;
            assert.deepEqual(applinth, data);

            const cloud = await getMongo().getCloudCollection(
                CollectionEnum.CLOUD,
            ).findOne({ instanceId: applinth.instanceId });

            const cloudData = JSON.parse(JSON.stringify(cloudBasicData));
            cloudData._id = cloud?._id;
            cloudData.created = cloud?.created;
            if (cloud) {
                cloud.closeDate = cloudData.closeDate;
                cloud.startDate = cloudData.startDate;
            }
            assert.deepEqual(cloud, cloudData);
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
            const resp = await supertest(getServer()).get('/applinths/123456789012123456789012');
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
            const resp = await supertest(getServer()).put('/applinths/123456789012123456789012').send({ tenantId: 'tenantId1' });
            assert.deepEqual(resp.statusCode, 400);
        });
    });
    describe('delete', () => {
        it('ok', async () => {
            const insertedId = Object.values((await createApplinths(basicData)).insertedIds)[0].toString();
            await createClouds(cloudBasicData);
            const resp = await supertest(getServer()).delete(`/applinths/${insertedId}`);
            assert.deepEqual(resp.statusCode, 200);
            assert.deepEqual(resp.body.msg, 'Entity successfully deleted!');
            const entity = await getBillingApiData(CollectionEnum.APPLINTH, insertedId);
            assert.deepEqual(entity, null);

            const cloudCount = await getMongo().getCloudCollection(
                CollectionEnum.CLOUD,
            ).countDocuments({ deleted: null });
            assert.deepEqual(cloudCount, 0);
        });
    });
});
