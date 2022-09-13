import * as crypto from 'crypto';
import { auth } from 'firebase-admin';
import { Collection } from 'mongodb';
import { ITenantCreateRequest } from '../controllers/tenants';
import TenantSearchError from '../errors/TenantSearchError';
import { authApp, usersService } from '../index';
import Tenant = auth.Tenant;
import { CollectionEnum } from '../enums/CollectionEnum';
import UserCreationError from '../errors/UserCreationError';
import BillingMongo from '../storage/mongo/Mongo';

export default class TenantService {

    public constructor(private readonly db: BillingMongo) {
    }

    public async getTenantList(): Promise<{ rows: unknown }> {
        const dbTenants = this.getTenantCollection().find({});
        const tenants = await authApp.auth().tenantManager().listTenants();
        const rows = [] as ITenant[];

        await dbTenants.forEach((dbTenant) => {
            const foundedTenant = tenants.tenants.find((tenant) => tenant.tenantId === dbTenant.tenantId);
            if (foundedTenant) {
                rows.push({
                    instanceId: dbTenant.instanceId as string,
                    ...foundedTenant,
                } as ITenant);
            }
        });

        return { rows: rows.map(this.mapTenantRecordToExport.bind(this)) };
    }

    public async getTenant(tenantId: string): Promise<{ tenant: unknown }> {
        let tenant;
        const dbTenant = await this.findOneTenant(tenantId);

        try {
            tenant = {
                instanceId: dbTenant.instanceId,
                ...await authApp.auth().tenantManager().getTenant(dbTenant.tenantId),
            } as ITenant;
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

        const generatedTenant = await this.createGeneratedTenant();

        const instanceId = crypto.randomBytes(5).toString('hex');

        let tenant;
        try {
            tenant = await this.updateCreatedGeneratedTenant(
                generatedTenant,
                createTenantRequest,
                createUser,
                instanceId,
            );
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
        const dbTenant = await this.findOneTenant(tenantId);

        try {
            tenant = {
                instanceId: dbTenant.instanceId,
                ...await authApp.auth().tenantManager().updateTenant(dbTenant.tenantId, updateTenantRequest),
            } as ITenant;
        } catch (e) {
            throw new TenantSearchError((e as Error).message);
        }

        return { tenant: this.mapTenantRecordToExport(tenant) };
    }

    public async deleteTenant(tenantId: string): Promise<{ msg: string }> {
        const dbTenant = await this.findOneTenant(tenantId);
        const users = await authApp.auth().tenantManager().authForTenant(dbTenant.tenantId).listUsers();
        const userIds = users.users.map((user) => user.uid);
        await authApp.auth().tenantManager().authForTenant(dbTenant.tenantId).deleteUsers(userIds);

        try {
            await this.getTenantCollection().findOneAndDelete({ tenantId });
            await authApp.auth().tenantManager().deleteTenant(dbTenant.tenantId);
        } catch (e) {
            throw new TenantSearchError((e as Error).message);
        }

        return { msg: 'Tenant successfully deleted!' };
    }

    private mapTenantRecordToExport(tenant: ITenant): unknown {
        return {
            instanceId: tenant.instanceId,
            tenantId: tenant.tenantId,
            displayName: tenant.displayName ?? undefined,
            emailSignInConfig: tenant.emailSignInConfig,
            anonymousSignInEnabled: tenant.anonymousSignInEnabled,
        };
    }

    private getTenantCollection(): Collection {
        return this.db.getCollection(CollectionEnum.TENANT);
    }

    private async findOneTenant(tenantId: string): Promise<IDbTenant> {
        return (await this.getTenantCollection().findOne({ tenantId })) as unknown as IDbTenant;
    }

    private async createGeneratedTenant(): Promise<Tenant> {
        const randomString = crypto.randomBytes(2).toString('hex');
        const generatedCreateTenantRequest = {
            displayName: `t${randomString}`,
            emailSignInConfig: {
                enabled: true,
                passwordRequired: true,
            },
        };

        return authApp.auth().tenantManager().createTenant(generatedCreateTenantRequest);
    }

    private async updateCreatedGeneratedTenant(
        generatedTenant: Tenant,
        createTenantRequest: ITenantCreateRequest,
        createUser: boolean,
        instanceId: string,
    ): Promise<ITenant> {
        const tenant = await authApp.auth().tenantManager()
            .updateTenant(generatedTenant.tenantId, { displayName: createTenantRequest.displayName }) as ITenant;

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

        await this.getTenantCollection().insertOne({ instanceId, tenantId: tenant.tenantId });
        tenant.instanceId = instanceId;

        return tenant;
    }

}

export interface ITenant extends Tenant, IDbTenant {}

export interface IDbTenant {
    instanceId: string;
    readonly tenantId: string;
}
