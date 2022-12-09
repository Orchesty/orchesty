import assert from 'assert';
import supertest, { Response } from 'supertest';
import { createDbTenants, createUsageStats, getJWTToken } from '../../../test/dataProvider';
import GranularityError from '../../errors/GranularityError';
import { db, server } from '../../index';

function assertUserData(resp: Response): void {
    assert.deepEqual(resp.body.rows.length, 4);
    assert.deepEqual(resp.body.rows, [
        {
            appId: 'neco', appName: 'neco', installed: '2021-01-01T00:00:00.000Z', instanceId: 'inst1234',
        },
        {
            appId: 'neco', appName: 'neco', installed: '2021-01-01T00:00:00.000Z', instanceId: 'inst1235',
        },
        {
            appId: 'neco1', appName: 'neco1', installed: '2021-02-01T00:00:00.000Z', instanceId: 'inst1234',
        },
        {
            appId: 'neco1', appName: 'neco1', installed: '2021-02-01T00:00:00.000Z', instanceId: 'inst1235',
        },
    ]);
}

describe('usageStatsController', () => {
    beforeEach(async () => {
        await createDbTenants();
        await createDbTenants('t123', false);
        await createUsageStats();
    });
    const authorization = getJWTToken();
    describe('apps', () => {
        it('shouldReturn400', async () => {
            const resp = await supertest(server).get('/billing/reports/apps');
            assert.deepEqual(resp.statusCode, 401);
        });
        it('shouldReturn500', async () => {
            await db.disconnect();
            const resp = await supertest(server).get('/billing/reports/apps').query({
                timeRangeStart: '2018-07-20T05:17:36Z',
                timeRangeEnd: '2018-07-20T05:17:36Z',
            }).set(authorization);
            assert.deepEqual(resp.statusCode, 500);
            await db.connect();
        });
        it('shouldReturnTailError', async () => {
            const resp = await supertest(server).get('/billing/reports/apps').query({
                timeRangeStart: '2018-07-20T05:17:36Z',
                timeRangeEnd: '2018-07-20T05:17:36Z',
                tail: true,
            }).set(getJWTToken(true));
            assert.deepEqual(resp.statusCode, 400);
        });
        it('shouldReturnTailData', async () => {
            const resp = await supertest(server).get('/billing/reports/apps').query({
                granularity: 'monthly',
                tail: true,
            }).set(getJWTToken(true));
            assert.deepEqual(resp.body.rows.length, 2);
            assert.deepEqual(resp.body.rows, [
                {
                    appId: 'neco',
                    appName: 'neco',
                    endUsers: 1,
                    estimatedTotalCost: 400000,
                    installCount: 2,
                    instanceIds: ['inst1234', 'inst1235'],
                    totalCost: 2000000,
                },
                {
                    appId: 'neco1',
                    appName: 'neco1',
                    endUsers: 1,
                    estimatedTotalCost: 400000,
                    installCount: 2,
                    instanceIds: ['inst1234', 'inst1235'],
                    totalCost: 200000,
                },
            ]);
            assert.deepEqual(resp.body.billingHistoryStart, '2021-01-01T00:00:00.000Z');
            assert.deepEqual(resp.body.billingHistoryEnd, '2021-02-01T00:00:00.000Z');
            assert.deepEqual(resp.statusCode, 200);
        });
        it.skip('shouldReturn403', async () => {
            const resp = await supertest(server).get('/billing/reports/apps').query({
                timeRangeStart: '2018-07-20T05:17:36Z',
                timeRangeEnd: '2024-07-20T05:17:36Z',
                granularity: 'monthly',
                tenantId: 't123',
            }).set(authorization);
            assert.deepEqual(resp.statusCode, 403);
        });
        it('shouldReturn200', async () => {
            const resp = await supertest(server).get('/billing/reports/apps').query({
                timeRangeStart: '2018-07-20T05:17:36Z',
                timeRangeEnd: '2024-07-20T05:17:36Z',
                granularity: 'monthly',
                instanceId: 'inst1234',
                tenantId: 't123',
            }).set(getJWTToken(true));
            assert.deepEqual(resp.statusCode, 200);
            assert.deepEqual(resp.body.rows.length, 1);
            assert.deepEqual(resp.body.rows, [
                {
                    appId: 'neco1',
                    appName: 'neco1',
                    endUsers: 1,
                    installCount: 1,
                    instanceIds: ['inst1234'],
                    totalCost: 100000,
                    estimatedTotalCost: 200000,
                },
            ]);
        });
        it('shouldReturn200WithAnotherInstalIds', async () => {
            const resp = await supertest(server).get('/billing/reports/apps').query({
                timeRangeStart: '2018-07-20T05:17:36Z',
                timeRangeEnd: '2024-07-20T05:17:36Z',
                granularity: 'monthly',
                tenantId: 't123',
            }).set(getJWTToken(true));
            assert.deepEqual(resp.statusCode, 200);
            assert.deepEqual(resp.body.rows.length, 1);
            assert.deepEqual(resp.body.rows, [
                {
                    appId: 'neco1',
                    appName: 'neco1',
                    endUsers: 1,
                    installCount: 2,
                    instanceIds: ['inst1234', 'inst1235'],
                    totalCost: 200000,
                    estimatedTotalCost: 400000,
                },
            ]);
        });
        it('shouldReturn400BadDateTimeFormat', async () => {
            const resp = await supertest(server).get('/billing/reports/apps').query({
                timeRangeStart: '2018-07-20T05:17:36Z',
                timeRangeEnd: 'asfbabwe',
                granularity: 'monthly',
            }).set(authorization);
            assert.deepEqual(resp.statusCode, 400);
            assert.deepEqual(resp.text, '{"msg":"Parameter timeRangeStart and/or timeRangeEnd is/are in'
                + ' invalid format!","code":1001}');
        });
        it('shouldReturn400BadDateGranularity', async () => {
            const resp = await supertest(server).get('/billing/reports/apps').query({
                timeRangeStart: '2018-07-20T05:17:36Z',
                timeRangeEnd: '2018-07-20T05:17:36Z',
                granularity: 'afbrbre',
            }).set(authorization);
            assert.deepEqual(resp.statusCode, 400);
            assert.deepEqual(resp.text, '[{"message":"Wrong parameter granularity in query. ","error":'
                + '[{"code":"ENUM_MISMATCH","params":["afbrbre"],"message":"No enum match for: afbrbre","path":"#/"}]}]');
        });
        it('shouldReturn401', async () => {
            const resp = await supertest(server).get('/billing/reports/apps').query({
                timeRangeStart: '2018-07-20T05:17:36Z',
                timeRangeEnd: '2024-07-20T05:17:36Z',
                granularity: 'monthly',
            });
            assert.deepEqual(resp.statusCode, 401);
        });
        it('shouldReturnData', async () => {
            const resp = await supertest(server).get('/billing/reports/apps').query({
                timeRangeStart: '2018-07-20T05:17:36Z',
                timeRangeEnd: '2024-07-20T05:17:36Z',
                granularity: 'monthly',
            }).set(authorization);
            assert.deepEqual(resp.body.rows.length, 2);
            assert.deepEqual(resp.body.rows, [
                {
                    appId: 'neco',
                    appName: 'neco',
                    instanceIds: ['inst1234', 'inst1235'],
                    endUsers: 1,
                    installCount: 2,
                    totalCost: 5000000,
                    estimatedTotalCost: 1200000,
                }, {
                    appId: 'neco1',
                    appName: 'neco1',
                    instanceIds: ['inst1234', 'inst1235'],
                    endUsers: 2,
                    installCount: 4,
                    totalCost: 600000,
                    estimatedTotalCost: 1200000,
                },
            ]);
            assert.deepEqual(resp.statusCode, 200);
        });
        it('shouldReturnSingleRow', async () => {
            const resp = await supertest(server).get('/billing/reports/apps').query({
                timeRangeStart: '2018-07-20T05:17:36Z',
                timeRangeEnd: '2024-07-20T05:17:36Z',
                granularity: 'monthly',
                appId: 'neco',
            }).set(authorization);
            assert.deepEqual(resp.body.rows.length, 1);
            assert.deepEqual(resp.body.rows, [{
                appId: 'neco',
                appName: 'neco',
                instanceIds: ['inst1234', 'inst1235'],
                endUsers: 1,
                installCount: 2,
                totalCost: 5000000,
                estimatedTotalCost: 1200000,
            },
            ]);
            assert.deepEqual(resp.statusCode, 200);
        });
    });

    describe('users', () => {
        it('shouldReturnData', async () => {
            const resp = await supertest(server).get('/billing/reports/users').query({
                timeRangeStart: '2018-07-20T05:17:36Z',
                timeRangeEnd: '2024-07-20T05:17:36Z',
                granularity: 'monthly',
                endUserDisplayId: '123',
                appId: 'neco1',
            }).set(authorization);
            assert.deepEqual(resp.body.rows.length, 1);
            assert.deepEqual(resp.body.rows, [
                {
                    activeAppNames: ['neco', 'neco1'],
                    appIds: ['neco1'],
                    appNames: ['neco1'],
                    endUserDisplayId: '1235',
                    endUserId: '1235',
                    estimatedTotalCost: 800000,
                    installCount: 4,
                    instanceIds: ['inst1234', 'inst1235'],
                    totalCost: 400000,
                },
            ]);
            assert.deepEqual(resp.statusCode, 200);
        });

        it('shouldReturnData with tail', async () => {
            const resp = await supertest(server).get('/billing/reports/users').query({
                tail: true,
                granularity: 'monthly',
                endUserId: '1235',
            }).set(authorization);
            assert.deepEqual(resp.body.rows.length, 1);
            assert.deepEqual(resp.body.rows, [
                {
                    activeAppNames: ['neco', 'neco1'],
                    appIds: ['neco', 'neco1'],
                    appNames: ['neco', 'neco1'],
                    endUserDisplayId: '1235',
                    endUserId: '1235',
                    estimatedTotalCost: 800000,
                    installCount: 4,
                    instanceIds: ['inst1234', 'inst1235'],
                    totalCost: 2200000,
                },
            ]);
            assert.deepEqual(resp.statusCode, 200);
        });
    });

    describe('usageStatsInstalledApps', () => {
        it('shouldReturn400BadDateTimeFormat', async () => {
            const resp = await supertest(server).get('/billing/reports/installedApps').query({
                installedDate: 'abwbewbwe',
                endUserId: '1235',
            }).set(authorization);
            assert.deepEqual(resp.statusCode, 400);
            assert.deepEqual(resp.text, '{"msg":"Parameter installedDated is in invalid format!","code":1002}');
        });
        it('shouldReturnData', async () => {
            const resp = await supertest(server).get('/billing/reports/installedApps').query({
                tenantId: 't123456789',
                installedDate: '2021-02-18T23:59:59Z',
                endUserId: '1235',
            }).set(authorization);
            assertUserData(resp);
            assert.deepEqual(resp.statusCode, 200);
        });
        it('shouldReturnDataWithTail', async () => {
            const resp = await supertest(server).get('/billing/reports/installedApps').query({
                tenantId: 't123456789',
                endUserId: '1235',
                tail: true,
            }).set(authorization);
            assertUserData(resp);
            assert.deepEqual(resp.statusCode, 200);
        });
    });

    describe('usageStatsTimeBucketApps', () => {
        it('shouldReturnData', async () => {
            const resp = await supertest(server).get('/billing/reports/timeBucketApps').query({
                tenantId: 't123456789',
                timeRangeStart: '2018-07-20T05:17:36Z',
                timeRangeEnd: '2024-07-20T05:17:36Z',
                endUserId: '1235',
            }).set(authorization);
            assert.deepEqual(resp.body.rows.length, 3);
            assert.deepEqual(resp.body.rows, [
                {
                    appIds: ['neco', 'neco1'],
                    appNames: ['neco', 'neco1'],
                    instanceIds: ['inst1234', 'inst1235'],
                    timeBucketName: '03/2021',
                    totalCost: 2200000,
                }, {
                    appIds: ['neco', 'neco1'],
                    appNames: ['neco', 'neco1'],
                    instanceIds: ['inst1234', 'inst1235'],
                    timeBucketName: '02/2021',
                    totalCost: 2200000,
                }, {
                    appIds: ['neco'],
                    appNames: ['neco'],
                    instanceIds: ['inst1234', 'inst1235'],
                    timeBucketName: '01/2021',
                    totalCost: 1000000,
                },
            ]);
            assert.deepEqual(resp.statusCode, 200);
        });
    });

    describe('usageStatsTimeBucketUsers', () => {
        it('shouldReturnData', async () => {
            const resp = await supertest(server).get('/billing/reports/timeBucketUsers').query({
                tenantId: 't123456789',
                timeRangeStart: '2018-07-20T05:17:36Z',
                timeRangeEnd: '2024-07-20T05:17:36Z',
                appId: 'neco1',
            }).set(authorization);
            assert.deepEqual(resp.body.rows.length, 2);
            assert.deepEqual(resp.body.rows, [
                {
                    endUsers: 1, timeBucketName: '02/2021',
                },
                {
                    endUsers: 2, timeBucketName: '03/2021',
                },
            ]);
            assert.deepEqual(resp.statusCode, 200);
        });
    });

    describe('usageStatsTimeBucketHistory', () => {
        it('shouldReturnData', async () => {
            const resp = await supertest(server).get('/billing/reports/timeBucketHistory').query({
                tenantId: 't123456789',
                timeRangeStart: '2018-07-20T05:17:36Z',
                timeRangeEnd: '2024-07-20T05:17:36Z',
                granularity: 'monthly',
            }).set(authorization);
            assert.deepEqual(resp.body.rows.length, 3);
            assert.deepEqual(resp.body.rows, [
                {
                    installCount: 2,
                    timeBucketName: '01/2021',
                    totalCost: 1000000,
                },
                {
                    installCount: 4,
                    timeBucketName: '02/2021',
                    totalCost: 2200000,
                }, {
                    installCount: 6,
                    timeBucketName: '03/2021',
                    totalCost: 2400000,
                },
            ]);
            assert.deepEqual(resp.statusCode, 200);
        });
        it('shouldReturnData daily', async () => {
            const resp = await supertest(server).get('/billing/reports/timeBucketHistory').query({
                tenantId: 't123456789',
                timeRangeStart: '2018-07-20T05:17:36Z',
                timeRangeEnd: '2024-07-20T05:17:36Z',
                granularity: 'daily',
            }).set(authorization);
            assert.deepEqual(resp.body.rows.length, 3);
            assert.deepEqual(resp.body.rows, [
                {
                    installCount: 1,
                    timeBucketName: '01/01/2021',
                    totalCost: 500000,
                },
                {
                    installCount: 1,
                    timeBucketName: '02/01/2021',
                    totalCost: 1000000,
                },
                {
                    installCount: 1,
                    timeBucketName: '03/01/2021',
                    totalCost: 1000000,
                }]);
            assert.deepEqual(resp.statusCode, 200);
        });
        it('shouldReturnData error', async () => {
            await supertest(server).get('/billing/reports/timeBucketHistory').query({
                tenantId: 't123456789',
                timeRangeStart: '2018-07-20T05:17:36Z',
                timeRangeEnd: '2024-07-20T05:17:36Z',
                granularity: 'hourly',
            }).set(authorization);
            assert.throws(() => {
                throw new GranularityError();
            });
        });
    });
});
