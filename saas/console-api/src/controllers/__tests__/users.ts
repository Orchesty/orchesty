import supertest from 'supertest';
import * as admin from 'firebase-admin';
import * as firebase from 'firebase/auth';
import assert from 'assert';
import {
  generateGetUsersResultMockedData,
  generateUsersExport,
  generateUserMockedData,
  getJWTToken,
} from '../../../test/dataProvider';
import { server } from '../../index';

const adminAuth = admin.auth().tenantManager().authForTenant('t1234');
describe('usersController', () => {
  beforeEach(() => {
    jest.spyOn(adminAuth, 'listUsers')
      .mockReturnValue(Promise.resolve({ users: [generateUserMockedData()] }));
    jest.spyOn(adminAuth, 'getUser')
      .mockReturnValue(Promise.resolve(generateUserMockedData()));
    jest.spyOn(adminAuth, 'getUsers')
      .mockReturnValue(Promise.resolve(generateGetUsersResultMockedData()));
    jest.spyOn(adminAuth, 'createUser')
      .mockReturnValue(Promise.resolve(generateUserMockedData()));
    jest.spyOn(adminAuth, 'updateUser')
      .mockReturnValue(Promise.resolve(generateUserMockedData('neco1')));
    jest.spyOn(adminAuth, 'deleteUser')
      .mockReturnValue(Promise.resolve());
  });

  const authorization = getJWTToken(true);
  describe('list', () => {
    it('shouldReturnDataForAll', async () => {
      const resp = await supertest(server).get('/users/list').query({ tenantId: 't1234' }).set(authorization);
      assert.deepEqual(resp.statusCode, 200);
      assert.deepEqual(resp.body.rows.length, 1);
      assert.deepEqual(resp.body, {
        rows: [generateUsersExport()],
      });
    });
    it('shouldReturnDataForFilter', async () => {
      const resp = await supertest(server).get('/users/list').query({ tenantId: 't1234', emails: ['neco@neco.com'] })
        .set(authorization);
      assert.deepEqual(resp.statusCode, 200);
      assert.deepEqual(resp.body.rows.length, 1);
      assert.deepEqual(resp.body, {
        rows: [generateUsersExport()],
      });
    });
    it('shouldReturn400ForAll', async () => {
      const resp = await supertest(server).get('/users/list').query({ tenantId: 't123' }).set(authorization);
      assert.deepEqual(resp.statusCode, 400);
    });
    it('shouldReturn400ForFilter', async () => {
      const resp = await supertest(server).get('/users/list').query({ tenantId: 't123', emails: ['neco'] })
        .set(authorization);
      assert.deepEqual(resp.statusCode, 400);
    });
    it('shouldReturn400ForFilter', async () => {
      const resp = await supertest(server).get('/users/list').query({ tenantId: 't123', emails: ['neco'] })
        .set(authorization);
      assert.deepEqual(resp.statusCode, 400);
    });
    it('shouldReturn403', async () => {
      const resp = await supertest(server).get('/users/list')
        .query({ tenantId: 't1234', emails: ['invalidEmail'] })
        .set(getJWTToken());
      assert.deepEqual(resp.statusCode, 403);
    });
  });

  describe('get', () => {
    it('shouldReturnData', async () => {
      const resp = await supertest(server).get('/users')
        .query({ tenantId: 't1234', uid: 'BjDKHoIseJR5zd0bixYnRR6Dt9i2' }).set(authorization);
      assert.deepEqual(resp.statusCode, 200);
      assert.deepEqual(resp.body, {
        user: generateUsersExport(),
      });
    });
  });

  describe('create', () => {
    it('shouldReturnData', async () => {
      const resp = await supertest(server).post('/users')
        .query({ tenantId: 't1234' }).set(authorization)
        .send({
          email: 'neco@neco.cz',
          displayName: 'neco',
        });
      assert.deepEqual(resp.statusCode, 200);
      assert.deepEqual(resp.body, {
        user: generateUsersExport('neco'),
      });
    });
  });

  describe('update', () => {
    it('shouldReturnData', async () => {
      const resp = await supertest(server).put('/users')
        .query({ tenantId: 't1234', uid: 'BjDKHoIseJR5zd0bixYnRR6Dt9i2' })
        .set(authorization)
        .send({ displayName: 'neco1' });
      assert.deepEqual(resp.statusCode, 200);
      assert.deepEqual(resp.body, {
        user: generateUsersExport('neco1'),
      });
    });
    it('shouldReturn400', async () => {
      jest.spyOn(adminAuth, 'updateUser')
        .mockImplementationOnce(() => { throw new Error(); });
      const resp = await supertest(server).put('/users')
        .query({ tenantId: 't1234', uid: 'BjDKHoIseJR5zd0bixYnRR6Dt9i2' })
        .set(authorization)
        .send({ displayName: 'neco1' });
      assert.deepEqual(resp.statusCode, 400);
    });
  });

  describe('delete', () => {
    it('shouldReturnData', async () => {
      const resp = await supertest(server).delete('/users')
        .query({ tenantId: 't1234', uid: 'BjDKHoIseJR5zd0bixYnRR6Dt9i2' })
        .set(authorization);
      assert.deepEqual(resp.statusCode, 200);
      assert.deepEqual(resp.body, { msg: 'User successfully deleted!' });
    });
    it('shouldReturn400', async () => {
      jest.spyOn(adminAuth, 'deleteUser')
        .mockImplementationOnce(() => { throw new Error(); });
      const resp = await supertest(server).delete('/users')
        .query({ tenantId: 't1234', uid: 'BjDKHoIseJR5zd0bixYnRR6Dt9i2' })
        .set(authorization);
      assert.deepEqual(resp.statusCode, 400);
    });
  });

  describe('sendResetPasswordEmail', () => {
    it('shouldReturnData', async () => {
      const resp = await supertest(server).post('/users/sendResetPasswordEmail').query({ tenantId: 't1234' })
        .set(authorization);

      assert.deepEqual(resp.statusCode, 200);
      assert.deepEqual(resp.body, { msg: 'Reset password link successfully sent!' });
    });
    it('shouldReturn400', async () => {
      jest.spyOn(firebase, 'sendPasswordResetEmail')
        .mockImplementationOnce(() => { throw new Error(); });
      const resp = await supertest(server).post('/users/sendResetPasswordEmail').query({ tenantId: 't1234' })
        .set(authorization);

      assert.deepEqual(resp.statusCode, 400);
    });
  });
});
