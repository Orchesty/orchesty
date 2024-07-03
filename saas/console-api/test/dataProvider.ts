import { Auth } from 'firebase/auth';
import { auth } from 'firebase-admin';
import {
    DeleteUsersResult,
    ListTenantsResult,
    Tenant as GTenant,
    UserRecord,
} from 'firebase-admin/lib/auth';
import { sign } from 'jsonwebtoken';
import { DateTime } from 'luxon';
import { Document, InsertManyResult, InsertOneResult, ObjectId, OptionalId } from 'mongodb';
import { CollectionEnum } from '../src/base/enums/CollectionEnum';
import { getAllResources } from '../src/base/enums/ResourceEnum';
import GetUsersResult = auth.GetUsersResult;
import { container } from '../src';
import Cloud from '../src/admin/entities/Cloud';
import { CloudPlan } from '../src/admin/enums/CloudPlan';
import { Period } from '../src/admin/enums/Period';
import Services from '../src/base/DIContainer/Services';
import Tenant from '../src/base/entities/Tenant';
import Mongo from '../src/base/storage/mongo/Mongo';

function generateUsageStatsRow(
    start: DateTime,
    end: DateTime,
    installed = false,
    appId = 'neco1',
    appName = 'neco1',
    endUserId = '1235',
    instanceId = 'inst1234',
    installId = 'i1235',
    tenantId = 't123456789',
    cost = 100000,
    estimatedCost = 200000,
): Document {
    return {
        appName,
        appId,
        endUserId,
        endUserDisplayId: endUserId,
        installId,
        instanceId,
        tenantId,
        start,
        end,
        duration: 1,
        billedDuration: 1,
        cost,
        estimatedCost,
        installed,
        type: 'enduser_app_install',
    };
}

function getDb(): Mongo {
    return container.get<Mongo>(Services.STORAGE);
}

export function generateAuth(): Auth {
    return {} as Auth;
}

export function generateTenantMockedData(name = 'neco'): GTenant {
    return {
        tenantId: 't-123456789',
        displayName: name || null,
        emailSignInConfig: {
            enabled: true,
            passwordRequired: true,
        },
        anonymousSignInEnabled: false,
    } as GTenant;
}

export function generateDbTenantMockedData(tenantId = ''): Tenant {
    return {
        instances: [{ instanceId: '1234567890' }],
        tenantId: tenantId || 't123456789',
        gTenantId: tenantId || 't-123456789',
    };
}

export function generateListTenantsResultMockedData(name = 'neco'): ListTenantsResult {
    return {
        tenants: [generateTenantMockedData(name)],
    };
}

export function generateDbApplinthMockedData(tenantId = ''): OptionalId<unknown> {
    return {
        instanceId: '1234567890',
        tenantId: tenantId || 't123456789',
        minPrice: 100000,
        minPriceDate: new Date('2022-07-28T08:21:20.000Z'),
    } as unknown as OptionalId<unknown>;
}

export function generateDbModuleMockedData(applinthId: string, appName: string): OptionalId<unknown> {
    return {
        applinthId,
        appName,
        price: 19900000,
    } as unknown as OptionalId<unknown>;
}

export function generateUserMockedData(name = 'neco'): UserRecord {
    return {
        uid: 'BjDKHoIseJR5zd0bixYnRR6Dt9i2',
        email: 'neco@neco.com',
        emailVerified: false,
        displayName: name,
        photoURL: undefined,
        disabled: false,
        metadata: {
            creationTime: 'Thu, 28 Jul 2022 08:21:20 GMT',
            lastSignInTime: 'Thu, 28 Jul 2022 08:21:20 GMT',
            toJSON(): object {
                return {};
            },
        },
        providerData: [],
        tokensValidAfterTime: 'Thu, 28 Jul 2022 08:21:20 GMT',
        tenantId: 't123456789',
        toJSON(): object {
            return {};
        },
    };
}

export function generateGetUsersResultMockedData(): GetUsersResult {
    return {
        users: [generateUserMockedData()],
        notFound: [],
    };
}

export function generateDeleteUsersResultMockedData(): DeleteUsersResult {
    return {
        errors: [],
        failureCount: 0,
        successCount: 1,
    };
}

export function generateUsersExport(name = 'neco'): unknown {
    return {
        uid: 'BjDKHoIseJR5zd0bixYnRR6Dt9i2',
        email: 'neco@neco.com',
        emailVerified: false,
        displayName: name,
        disabled: false,
        metadata: {
            creationTime: 'Thu, 28 Jul 2022 08:21:20 GMT',
            lastSignTime: 'Thu, 28 Jul 2022 08:21:20 GMT',
        },
        providerData: [],
        tokensValidAfterTime: 'Thu, 28 Jul 2022 08:21:20 GMT',
        tenantId: 't123456789',
    };
}

export function generateTenantsExport(name = 'neco'): unknown {
    let tenant = {
        instances: [{ instanceId: '1234567890' }],
        tenantId: 't123456789',
        gTenantId: 't-123456789',
        emailSignInConfig: {
            enabled: true,
            passwordRequired: true,
        },
        anonymousSignInEnabled: false,
    };

    if (name) {
        tenant = Object.assign(tenant, {
            displayName: name,
        });
    }

    return tenant;
}

export async function createDbTenants(tenantId = '', drop = true): Promise<void> {
    if (drop) {
        await getDb().getCloudCollection(CollectionEnum.TENANT).drop();
    }
    await getDb().getCloudCollection(CollectionEnum.TENANT).insertOne(generateDbTenantMockedData(tenantId));
}

export async function createDbApplinth(tenantId: string, drop = true): Promise<InsertOneResult<unknown>> {
    if (drop) {
        await getDb().getCloudCollection(CollectionEnum.APPLINTH).drop();
    }
    return getDb()
        .getCloudCollection(CollectionEnum.APPLINTH)
        .insertOne(generateDbApplinthMockedData(tenantId));
}

export async function createDbModule(applinthId: string, appName: string, drop = true): Promise<void> {
    if (drop) {
        await getDb().getCloudCollection(CollectionEnum.MODULE).drop();
    }
    await getDb().getCloudCollection(CollectionEnum.MODULE).insertOne(generateDbModuleMockedData(applinthId, appName));
}

export async function dropMetadata(): Promise<void> {
    await getDb().getBillingCollection(CollectionEnum.USAGE_STATS_METADATA)
        .drop();
}

export async function createUsageStats(): Promise<void> {
    await getDb().getBillingCollection(CollectionEnum.USAGE_STATS_MONTHLY)
        .drop();
    await getDb().getBillingCollection(CollectionEnum.USAGE_STATS_DAILY)
        .drop();
    const startDate1 = DateTime.local(2021, 1, 1);
    const endDate1 = DateTime.local(2021, 1, 1)
        .endOf('month');
    const startDate2 = DateTime.local(2021, 2, 1);
    const endDate2 = DateTime.local(2021, 2, 1)
        .endOf('month');
    const startDate3 = DateTime.local(2021, 3, 1);
    const endDate3 = DateTime.local(2021, 3, 1)
        .endOf('month');

    await getDb().getBillingCollection(CollectionEnum.USAGE_STATS_MONTHLY)
        .insertMany([
            generateUsageStatsRow(startDate1, endDate1, false, 'neco', 'neco', '1235', 'inst1234', 'i1234', 't123456789', 500000),
            generateUsageStatsRow(startDate2, endDate2, false, 'neco', 'neco', '1235', 'inst1234', 'i1234', 't123456789', 1000000),
            generateUsageStatsRow(startDate3, endDate3, true, 'neco', 'neco', '1235', 'inst1234', 'i1234', 't123456789', 1000000),
            generateUsageStatsRow(startDate2, endDate2),
            generateUsageStatsRow(startDate3, endDate3, true),
            generateUsageStatsRow(startDate3, endDate3, false, 'neco1', 'neco1', '1234', 'inst1234', 'i1236'),
            generateUsageStatsRow(startDate3, endDate3, false, 'neco1', 'neco1', '1234', 'inst1234', 'i1236', 't123'),
            generateUsageStatsRow(startDate1, endDate1, false, 'neco', 'neco', '1235', 'inst1235', 'i1237', 't123456789', 500000),
            generateUsageStatsRow(startDate2, endDate2, false, 'neco', 'neco', '1235', 'inst1235', 'i1237', 't123456789', 1000000),
            generateUsageStatsRow(startDate3, endDate3, true, 'neco', 'neco', '1235', 'inst1235', 'i1237', 't123456789', 1000000),
            generateUsageStatsRow(startDate2, endDate2, false, 'neco1', 'neco1', '1235', 'inst1235', 'i1238'),
            generateUsageStatsRow(startDate3, endDate3, true, 'neco1', 'neco1', '1235', 'inst1235', 'i1238'),
            generateUsageStatsRow(startDate3, endDate3, false, 'neco1', 'neco1', '1234', 'inst1235', 'i1239'),
            generateUsageStatsRow(startDate3, endDate3, false, 'neco1', 'neco1', '1234', 'inst1235', 'i1239', 't123'),
        ]);

    await getDb().getBillingCollection(CollectionEnum.USAGE_STATS_DAILY)
        .insertMany([
            generateUsageStatsRow(DateTime.local(2021, 1, 1), DateTime.local(2021, 1, 1).endOf('day'), false, 'neco', 'neco', '1235', 'inst1234', 'i1234', 't123456789', 500000),
            generateUsageStatsRow(DateTime.local(2021, 1, 2), DateTime.local(2021, 1, 2).endOf('day'), false, 'neco', 'neco', '1235', 'inst1234', 'i1234', 't123456789', 1000000),
            generateUsageStatsRow(DateTime.local(2021, 1, 3), DateTime.local(2021, 1, 3).endOf('day'), true, 'neco', 'neco', '1235', 'inst1234', 'i1234', 't123456789', 1000000),
        ]);

    await getDb().getBillingCollection(CollectionEnum.USAGE_STATS_METADATA)
        .insertMany([
            {
                tenantId: 't123',
                instances: {
                    inst1234: {
                        billingHistoryStart: DateTime.local(2021, 1, 1),
                        billingHistoryEnd: DateTime.local(2021, 2, 1),
                    },
                },
            },
            {
                tenantId: 't123456789',
                instances: {
                    inst1234: {
                        billingHistoryStart: DateTime.local(2021, 1, 1),
                        billingHistoryEnd: DateTime.local(2021, 2, 1),
                    },
                },
            },
        ]);
}

export function getJWTToken(withPermissions = false): { authorization: string } {
    const token = sign({
        /* eslint-disable @typescript-eslint/naming-convention */
        firebase: { tenant: 't-123456789' },
        first_name: 'John',
        last_name: 'Doe',
        /* eslint-enable @typescript-eslint/naming-convention */
        email: 'john.doe@mail.com',
        permissions: withPermissions ? getAllResources() : [],
    }, 'secretPass');

    return {
        authorization: `Bearer ${token}`,
    };
}

export async function createBillingApiData(collection: string, data: object[]): Promise<InsertManyResult> {
    return getDb().getCloudCollection(collection).insertMany(data);
}

export async function getBillingApiData(collection: string, _id: string): Promise<unknown> {
    return getDb().getCloudCollection(collection).findOne({
        _id: new ObjectId(_id),
        deleted: {
            $exists: false,
        },
    });
}

export const cloudBasicData = {
    created: null,
    updated: null,
    deleted: null,
    tenantId: 'tenantId',
    instanceId: 'instanceId',
    plan: CloudPlan.BASIC,
    price: 0,
    period: Period.MONTHLY,
    startDate: new Date('2022-01-01'),
    closeDate: new Date('2022-01-01'),
} as Cloud;

export async function createClouds(data: Cloud = cloudBasicData): Promise<InsertManyResult> {
    return createBillingApiData(CollectionEnum.CLOUD, [data]);
}
