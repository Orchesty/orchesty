import * as crypto from 'crypto';
import { auth } from 'firebase-admin';
import { ITenantCreateRequest } from '../controllers/tenants';
import TenantSearchError from '../errors/TenantSearchError';
import { authApp, usersService } from '../index';
import Tenant = auth.Tenant;
import UserCreationError from '../errors/UserCreationError';

export default class TenantService {

    public async getTenantList(): Promise<{ rows: unknown }> {
        const tenants = await authApp.auth().tenantManager().listTenants();
        return { rows: tenants.tenants.map(this.mapTenantRecordToExport.bind(this)) };
    }

    public async getTenant(tenantId: string): Promise<{ tenant: unknown }> {
        let tenant;

        try {
            tenant = await authApp.auth().tenantManager().getTenant(tenantId);
        } catch (e) {
            throw new TenantSearchError((e as Error).message);
        }

        return { tenant: this.mapTenantRecordToExport(tenant) };
    }

    public async createTenant(createTenantRequest: ITenantCreateRequest): Promise<{ tenant: unknown }> {
        let createUser = false;
        if (createTenantRequest.email || createTenantRequest.userDisplayName) {
            if (createTenantRequest.email && createTenantRequest.userDisplayName) {
                createUser = true;
            } else {
                throw new UserCreationError('You must enter email and userDisplayName when creating initial user for tenant');
            }
        }

        const randomString = crypto.randomBytes(2).toString('hex');
        const generatedCreateTenantRequest = {
            displayName: `t${randomString}`,
            emailSignInConfig: {
                enabled: true,
                passwordRequired: true,
            },
        };

        const generatedTenant = await authApp.auth().tenantManager().createTenant(generatedCreateTenantRequest);

        let tenant;
        try {
            tenant = await authApp.auth().tenantManager()
                .updateTenant(generatedTenant.tenantId, { displayName: createTenantRequest.displayName });

            if (createUser) {
                await usersService.createUser(
                    {
                        tenantId: tenant.tenantId,
                    },
                    {
                        email: createTenantRequest.email,
                        displayName: createTenantRequest.userDisplayName,
                    },
                    tenant.tenantId,
                );
            }
        } catch (e) {
            throw new TenantSearchError((e as Error).message);
        }

        return { tenant: this.mapTenantRecordToExport(tenant) };
    }

    public async updateTenant(
        tenantId: string,
        updateTenantRequest: ITenantCreateRequest,
    ): Promise<{ tenant: unknown }> {
        let tenant;

        try {
            tenant = await authApp.auth().tenantManager().updateTenant(tenantId, updateTenantRequest);
        } catch (e) {
            throw new TenantSearchError((e as Error).message);
        }

        return { tenant: this.mapTenantRecordToExport(tenant) };
    }

    public async deleteTenant(tenantId: string): Promise<{ msg: string }> {
        const users = await authApp.auth().tenantManager().authForTenant(tenantId).listUsers();
        const userIds = users.users.map((user) => user.uid);
        await authApp.auth().tenantManager().authForTenant(tenantId).deleteUsers(userIds);

        try {
            await authApp.auth().tenantManager().deleteTenant(tenantId);
        } catch (e) {
            throw new TenantSearchError((e as Error).message);
        }

        return { msg: 'Tenant successfully deleted!' };
    }

    private mapTenantRecordToExport(tenant: Tenant): unknown {
        return {
            tenantId: tenant.tenantId,
            displayName: tenant.displayName ?? null,
            emailSignInConfig: tenant.emailSignInConfig,
            anonymousSignInEnabled: tenant.anonymousSignInEnabled,
        };
    }

}
