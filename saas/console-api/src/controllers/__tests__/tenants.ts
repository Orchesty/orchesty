import supertest from 'supertest';
import * as admin from 'firebase-admin';
import assert from 'assert';
import {
  generateDeleteUsersResultMockedData,
  generateListTenantsResultMockedData, generateTenantMockedData, generateTenantsExport, generateUserMockedData,
  getJWTToken,
} from '../../../test/dataProvider';
import { server } from '../../index';

const tenantManager = admin.auth().tenantManager();
const adminAuth = admin.auth().tenantManager().authForTenant('t123');
describe('tenantsController', () => {
  beforeEach(() => {
    jest.spyOn(tenantManager, 'listTenants')
      .mockReturnValue(Promise.resolve(generateListTenantsResultMockedData('')));
    jest.spyOn(tenantManager, 'getTenant')
      .mockReturnValue(Promise.resolve(generateTenantMockedData()));
    jest.spyOn(tenantManager, 'createTenant')
      .mockReturnValue(Promise.resolve(generateTenantMockedData()));
    jest.spyOn(tenantManager, 'updateTenant')
      .mockReturnValue(Promise.resolve(generateTenantMockedData()));
    jest.spyOn(tenantManager, 'deleteTenant')
      .mockReturnValue(Promise.resolve());
    jest.spyOn(adminAuth, 'listUsers')
      .mockReturnValue(Promise.resolve({ users: [generateUserMockedData()] }));
    jest.spyOn(admin.auth().tenantManager().authForTenant('t1234'), 'createUser')
      .mockReturnValue(Promise.resolve(generateUserMockedData()));
    jest.spyOn(adminAuth, 'deleteUsers')
      .mockReturnValue(Promise.resolve(generateDeleteUsersResultMockedData()));
  });

  const authorization = getJWTToken(true);
  describe('list', () => {
    it('shouldReturnData', async () => {
      const resp = await supertest(server).get('/tenants').set(authorization);
      assert.deepEqual(resp.statusCode, 200);
      assert.deepEqual(resp.body.rows.length, 1);
      assert.deepEqual(resp.body, {
        rows: [generateTenantsExport('')],
      });
    });
    it('shouldReturn403', async () => {
      const resp = await supertest(server).get('/tenants')
        .set(getJWTToken());
      assert.deepEqual(resp.statusCode, 403);
    });
  });

  describe('get', () => {
    it('shouldReturnData', async () => {
      const resp = await supertest(server).get('/tenants/t1234').set(authorization);
      assert.deepEqual(resp.statusCode, 200);
      assert.deepEqual(resp.body, {
        tenant: generateTenantsExport(),
      });
    });
    it('shouldReturn400', async () => {
      jest.spyOn(tenantManager, 'getTenant')
        .mockImplementationOnce(() => { throw new Error(); });
      const resp = await supertest(server).get('/tenants/t123').set(authorization);
      assert.deepEqual(resp.statusCode, 400);
    });
  });

  describe('create', () => {
    it('shouldReturnData', async () => {
      const resp = await supertest(server).post('/tenants').set(authorization).send({
        displayName: 'neco',
      });
      assert.deepEqual(resp.statusCode, 200);
      assert.deepEqual(resp.body, {
        tenant: generateTenantsExport(),
      });
    });
    it('shouldReturn 400', async () => {
      const resp = await supertest(server).post('/tenants').set(authorization).send({
        displayName: 'neco',
        email: 'neco@neco.cz',
      });
      assert.deepEqual(resp.statusCode, 400);
    });
    it('shouldCreateUser', async () => {
      const resp = await supertest(server).post('/tenants').set(authorization).send({
        displayName: 'neco',
        email: 'neco@neco.cz',
        userDisplayName: 'neco',
      });
      assert.deepEqual(resp.statusCode, 200);
      assert.deepEqual(resp.body, {
        tenant: generateTenantsExport(),
      });
    });
  });

  describe('update', () => {
    it('shouldReturnData', async () => {
      jest.spyOn(tenantManager, 'updateTenant')
        .mockReturnValue(Promise.resolve(generateTenantMockedData('neco1')));
      const resp = await supertest(server).put('/tenants/t1234').set(authorization)
        .send({
          displayName: 'neco1',
        });
      assert.deepEqual(resp.statusCode, 200);
      assert.deepEqual(resp.body, {
        tenant: generateTenantsExport('neco1'),
      });
    });
  });

  describe('delete', () => {
    it('shouldReturnData', async () => {
      const resp = await supertest(server).delete('/tenants/t123').set(authorization);
      assert.deepEqual(resp.statusCode, 200);
      assert.deepEqual(resp.body, { msg: 'Tenant successfully deleted!' });
    });
    it('shouldReturn403', async () => {
      const resp = await supertest(server).delete('/tenants/t1234').set(authorization);
      assert.deepEqual(resp.statusCode, 403);
    });
  });
});
