import * as crypto from 'crypto';
import { auth } from 'firebase-admin';
import { Collection } from 'mongodb';
import { ITenantCreateRequest } from '../controllers/tenants';
import TenantSearchError from '../errors/TenantSearchError';
import { authApp, usersService } from '../index';
import Tenant = auth.Tenant;
import { CollectionEnum } from '../enums/CollectionEnum';
import UserCreationError from '../errors/UserCreationError';
import Mongo from '../storage/mongo/Mongo';

export default class TenantService {

    public constructor(private readonly db: Mongo) {
    }

    public async getTenantList(): Promise<{ rows: unknown }> {
        const dbTenants = this.getTenantCollection().find({});
        const tenants = await authApp.auth().tenantManager().listTenants();
        const rows = [] as ITenant[];

        await dbTenants.forEach((dbTenant) => {
            const foundedTenant = tenants.tenants.find((tenant) => tenant.tenantId === dbTenant.gTenantId);
            if (foundedTenant) {
                rows.push({
                    instances: dbTenant.instances,
                    tenantId: dbTenant.tenantId,
                    gTenantId: dbTenant.gTenantId,
                    gTenant: foundedTenant,
                } as ITenant);
            }
        });

        return { rows: rows.map(this.mapTenantRecordToExport.bind(this)) };
    }

    public async getTenant(gTenantId: string): Promise<{ tenant: unknown }> {
        let tenant;
        const dbTenant = await this.findOneTenant(gTenantId);

        try {
            const gTenant = await authApp.auth().tenantManager().getTenant(dbTenant.gTenantId);
            tenant = {
                instances: dbTenant.instances,
                tenantId: dbTenant.tenantId,
                gTenantId: gTenant.tenantId,
                gTenant,
            } as ITenant;

            return { tenant: this.mapTenantRecordToExport(tenant) };
        } catch (e) {
            throw new TenantSearchError((e as Error).message);
        }
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

        try {
            const tenant = await this.updateCreatedGeneratedTenant(
                generatedTenant,
                createTenantRequest,
                createUser,
                [{ instanceId }],
                `t-${instanceId}`,
            );

            return { tenant: this.mapTenantRecordToExport(tenant) };
        } catch (e) {
            throw new TenantSearchError((e as Error).message);
        }
    }

    public async updateTenant(
        gTenantId: string,
        updateTenantRequest: ITenantCreateRequest,
    ): Promise<{ tenant: unknown }> {
        let tenant;
        let gTenant;
        const dbTenant = await this.findOneTenant(gTenantId);

        try {
            gTenant = await authApp.auth().tenantManager().updateTenant(dbTenant.tenantId, updateTenantRequest);
            tenant = {
                instances: dbTenant.instances,
                tenantId: dbTenant.tenantId,
                gTenantId: gTenant.tenantId,
                gTenant,
            } as ITenant;
        } catch (e) {
            throw new TenantSearchError((e as Error).message);
        }

        return { tenant: this.mapTenantRecordToExport(tenant) };
    }

    public async deleteTenant(gTenantId: string): Promise<{ msg: string }> {
        const dbTenant = await this.findOneTenant(gTenantId);
        const users = await authApp.auth().tenantManager().authForTenant(dbTenant.gTenantId).listUsers();
        const userIds = users.users.map((user) => user.uid);
        await authApp.auth().tenantManager().authForTenant(dbTenant.gTenantId).deleteUsers(userIds);

        try {
            await this.getTenantCollection().findOneAndDelete({ tenantId: dbTenant.tenantId });
            await authApp.auth().tenantManager().deleteTenant(dbTenant.tenantId);
        } catch (e) {
            throw new TenantSearchError((e as Error).message);
        }

        return { msg: 'Tenant successfully deleted!' };
    }

    private mapTenantRecordToExport(tenant: ITenant): unknown {
        return {
            instances: tenant.instances,
            tenantId: tenant.tenantId,
            gTenantId: tenant.gTenant?.tenantId,
            displayName: tenant.gTenant?.displayName ?? undefined,
            emailSignInConfig: tenant.gTenant?.emailSignInConfig,
            anonymousSignInEnabled: tenant.gTenant?.anonymousSignInEnabled,
        };
    }

    private getTenantCollection(): Collection {
        return this.db.getCloudCollection(CollectionEnum.TENANT);
    }

    private async findOneTenant(gTenantId: string): Promise<ITenant> {
        return (await this.getTenantCollection().findOne({ gTenantId })) as unknown as ITenant;
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
        instances: IInstance[],
        tenantId: string,
    ): Promise<ITenant> {
        const gTenant = await authApp.auth().tenantManager()
            .updateTenant(generatedTenant.tenantId, { displayName: createTenantRequest.displayName });

        if (createUser) {
            await usersService.createUser(
                {
                    tenantId: generatedTenant.tenantId,
                },
                {
                    email: createTenantRequest.email,
                    displayName: createTenantRequest.userDisplayName,
                },
                generatedTenant.tenantId,
                tenantId,
            );
        }

        const tenant = { instances, tenantId, gTenantId: gTenant.tenantId } as ITenant;
        await this.getTenantCollection().insertOne(tenant);
        tenant.gTenant = gTenant;

        return tenant;
    }

}

export interface ITenant {
    instances: IInstance[];
    tenantId: string;
    gTenantId: string;
    gTenant?: Tenant;
}

export interface IInstance {
    instanceId: string;
}
