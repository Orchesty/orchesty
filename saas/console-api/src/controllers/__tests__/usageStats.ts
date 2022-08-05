import supertest from 'supertest';
import assert from 'assert';
import { db, server } from '../../index';
import { createUsageStats, getJWTToken } from '../../../test/dataProvider';

describe('usageStatsController', () => {
  beforeEach(async () => {
    await createUsageStats();
  });
  const authorization = getJWTToken();
  describe('apps', () => {
    it('shouldReturn400', async () => {
      const resp = await supertest(server).get('/billing/reports/apps');
      assert.deepEqual(resp.statusCode, 400);
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
    it('shouldReturn403', async () => {
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
        tenantId: 't123',
      }).set(getJWTToken(true));
      assert.deepEqual(resp.statusCode, 200);
      assert.deepEqual(resp.body.rows.length, 1);
      assert.deepEqual(resp.body.rows, [
        {
          appName: 'neco1', endUsers: 1, installCount: 1, totalCost: 100000,
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
          appName: 'neco', endUsers: 1, installCount: 3, totalCost: 2500000,
        }, {
          appName: 'neco1', endUsers: 2, installCount: 3, totalCost: 300000,
        },
      ]);
      assert.deepEqual(resp.statusCode, 200);
    });
    it('shouldReturnSingleRow', async () => {
      const resp = await supertest(server).get('/billing/reports/apps').query({
        timeRangeStart: '2018-07-20T05:17:36Z',
        timeRangeEnd: '2024-07-20T05:17:36Z',
        granularity: 'monthly',
        appName: 'neco',
      }).set(authorization);
      assert.deepEqual(resp.body.rows.length, 1);
      assert.deepEqual(resp.body.rows, [{
        appName: 'neco', endUsers: 1, installCount: 3, totalCost: 2500000,
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
      }).set(authorization);
      assert.deepEqual(resp.body.rows.length, 2);
      assert.deepEqual(resp.body.rows, [
        {
          appNames: ['neco1'], endUserDisplayId: '1234', endUserId: '1234', installCount: 1, totalCost: 100000,
        },
        {
          appNames: ['neco', 'neco1'], endUserDisplayId: '1235', endUserId: '1235', installCount: 5, totalCost: 2700000,
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
        tenantId: 't1234',
        installedDate: '2021-02-18T23:59:59Z',
        endUserId: '1235',
      }).set(authorization);
      assert.deepEqual(resp.body.rows.length, 2);
      assert.deepEqual(resp.body.rows, [
        {
          appName: 'neco', installed: '2020-12-31T23:00:00.000Z',
        },
        {
          appName: 'neco1', installed: '2021-01-31T23:00:00.000Z',
        },
      ]);
      assert.deepEqual(resp.statusCode, 200);
    });
  });

  describe('usageStatsTimeBucketApps', () => {
    it('shouldReturnData', async () => {
      const resp = await supertest(server).get('/billing/reports/timeBucketApps').query({
        tenantId: 't1234',
        timeRangeStart: '2018-07-20T05:17:36Z',
        timeRangeEnd: '2024-07-20T05:17:36Z',
        endUserId: '1235',
      }).set(authorization);
      assert.deepEqual(resp.body.rows.length, 3);
      assert.deepEqual(resp.body.rows, [
        {
          appNames: ['neco'], formattedDate: '12/20', totalCost: 500000,
        },
        {
          appNames: ['neco', 'neco1'], formattedDate: '01/21', totalCost: 1100000,
        },
        {
          appNames: ['neco', 'neco1'], formattedDate: '02/21', totalCost: 1100000,
        },
      ]);
      assert.deepEqual(resp.statusCode, 200);
    });
  });

  describe('usageStatsTimeBucketUsers', () => {
    it('shouldReturnData', async () => {
      const resp = await supertest(server).get('/billing/reports/timeBucketUsers').query({
        tenantId: 't1234',
        timeRangeStart: '2018-07-20T05:17:36Z',
        timeRangeEnd: '2024-07-20T05:17:36Z',
        appName: 'neco1',
      }).set(authorization);
      assert.deepEqual(resp.body.rows.length, 2);
      assert.deepEqual(resp.body.rows, [
        {
          endUsers: 1, formattedDate: '01/21',
        },
        {
          endUsers: 2, formattedDate: '02/21',
        },
      ]);
      assert.deepEqual(resp.statusCode, 200);
    });
  });
});
