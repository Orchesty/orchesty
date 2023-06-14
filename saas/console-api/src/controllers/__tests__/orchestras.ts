import assert from 'assert';
import { Express } from 'express';
import { InsertManyResult } from 'mongodb';
import supertest from 'supertest';
import { mockAdapter } from '../../../.jest/testLifecycle';
import { cloudBasicData, createBillingApiData, createClouds, getBillingApiData } from '../../../test/dataProvider';
import Orchesty from '../../admin/entities/Orchesty';
import { OrchestyVersion } from '../../admin/enums/OrchestyVersion';
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
    version: OrchestyVersion.HOSTED,
    price: 0,
    startDate: new Date('2022-01-01'),
} as Orchesty;

function getServer(): Express {
    return container.get<Express>(Services.SERVER);
}

function getMongo(): Mongo {
    return container.get<Mongo>(Services.STORAGE);
}

async function createOrchestras(data: Orchesty = basicData): Promise<InsertManyResult> {
    return createBillingApiData(CollectionEnum.ORCHESTY, [data]);
}

describe('orchestrasController', () => {
    beforeAll(() => {
        mockAdapter.onPut('http://usscp').reply(200);
    });
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
            const resp = await supertest(getServer()).post('/orchestras').send({
                ...basicData,
                cloud: cloudBasicData,
            });
            assert.deepEqual(resp.statusCode, 200);
            const data = JSON.parse(JSON.stringify(basicData));
            const { orchesty } = resp.body;
            data._id = orchesty._id;
            data.created = orchesty.created;
            assert.deepEqual(orchesty, data);

            const cloud = await getMongo().getCloudCollection(
                CollectionEnum.CLOUD,
            ).findOne({ instanceId: orchesty.instanceId });

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
            await createClouds(cloudBasicData);
            const resp = await supertest(getServer()).delete(`/orchestras/${insertedId}`);
            assert.deepEqual(resp.statusCode, 200);
            assert.deepEqual(resp.body.msg, 'Entity successfully deleted!');
            const entity = await getBillingApiData(CollectionEnum.ORCHESTY, insertedId);
            assert.deepEqual(entity, null);

            const cloudCount = await getMongo().getCloudCollection(
                CollectionEnum.CLOUD,
            ).countDocuments({ deleted: null });
            assert.deepEqual(cloudCount, 0);
        });
    });
});
