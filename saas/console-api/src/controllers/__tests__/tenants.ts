import assert from 'assert';
import * as admin from 'firebase-admin';
import { GoogleOAuthAccessToken } from 'firebase-admin';
import supertest from 'supertest';
import { fetchMock } from '../../../.jest/testLifecycle';
import {
    createDbTenants,
    generateDeleteUsersResultMockedData,
    generateListTenantsResultMockedData,
    generateTenantMockedData,
    generateTenantsExport,
    generateUserMockedData,
    getJWTToken,
} from '../../../test/dataProvider';
import { authApp, server } from '../../index';

const tenantManager = admin.auth().tenantManager();
const adminAuth = admin.auth().tenantManager().authForTenant('t123');
const userAdminAuth = admin.auth().tenantManager().authForTenant('t-123456789');
const { credential } = authApp.options;

describe('tenantsController', () => {
    beforeAll(() => {
        fetchMock.patch('https://identitytoolkit.googleapis.com/v2/projects/undefined/tenants/t-123456789', 200);
    });
    beforeEach(async () => {
        await createDbTenants();
        jest.spyOn(tenantManager, 'listTenants')
            .mockResolvedValue(generateListTenantsResultMockedData(''));
        jest.spyOn(tenantManager, 'getTenant')
            .mockResolvedValue(generateTenantMockedData());
        jest.spyOn(tenantManager, 'createTenant')
            .mockResolvedValue(generateTenantMockedData());
        jest.spyOn(tenantManager, 'updateTenant')
            .mockResolvedValue(generateTenantMockedData());
        jest.spyOn(tenantManager, 'deleteTenant')
            .mockResolvedValue(undefined);
        jest.spyOn(adminAuth, 'listUsers')
            .mockResolvedValue({ users: [generateUserMockedData()] });
        jest.spyOn(userAdminAuth, 'createUser')
            .mockResolvedValue(generateUserMockedData());
        jest.spyOn(userAdminAuth, 'getUser')
            .mockResolvedValue(generateUserMockedData());
        jest.spyOn(userAdminAuth, 'setCustomUserClaims')
            .mockResolvedValue();
        jest.spyOn(adminAuth, 'deleteUsers')
            .mockResolvedValue(generateDeleteUsersResultMockedData());
        if (credential) {
            jest.spyOn(credential, 'getAccessToken')
                .mockResolvedValue({ access_token: 'neco' } as unknown as GoogleOAuthAccessToken);
        }
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
        it.skip('shouldReturn403', async () => {
            const resp = await supertest(server).get('/tenants')
                .set(getJWTToken());
            assert.deepEqual(resp.statusCode, 403);
        });
    });

    describe('get', () => {
        it('shouldReturnData', async () => {
            const resp = await supertest(server).get('/tenants/t123456789').set(authorization);
            assert.deepEqual(resp.statusCode, 200);
            assert.deepEqual(resp.body, {
                tenant: generateTenantsExport(),
            });
        });
        it('shouldReturn400', async () => {
            jest.spyOn(tenantManager, 'getTenant')
                .mockImplementationOnce(() => {
                    throw new Error();
                });
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
            resp.body.tenant.instances = [{ instanceId: '1234567890' }];
            resp.body.tenant.tenantId = 't123456789';
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
            resp.body.tenant.instances = [{ instanceId: '1234567890' }];
            resp.body.tenant.tenantId = 't123456789';
            assert.deepEqual(resp.body, {
                tenant: generateTenantsExport(),
            });
        });
    });

    describe('update', () => {
        it('shouldReturnData', async () => {
            jest.spyOn(tenantManager, 'updateTenant')
                .mockResolvedValue(generateTenantMockedData('neco1'));
            const resp = await supertest(server).put('/tenants/t123456789').set(authorization)
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
        beforeEach(async () => {
            await createDbTenants('t123', false);
        });
        it('shouldReturnData', async () => {
            jest.spyOn(admin.auth().tenantManager().authForTenant('t123'), 'listUsers')
                .mockResolvedValue({ users: [generateUserMockedData()] });
            const resp = await supertest(server).delete('/tenants/t123').set(authorization);
            assert.deepEqual(resp.statusCode, 200);
            assert.deepEqual(resp.body, { msg: 'Tenant successfully deleted!' });
        });
        it('shouldReturn403', async () => {
            const resp = await supertest(server).delete('/tenants/t123456789').set(authorization);
            assert.deepEqual(resp.statusCode, 403);
        });
    });
});
