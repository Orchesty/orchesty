import { Auth } from 'firebase/auth';
import { auth } from 'firebase-admin';
import {
    DeleteUsersResult,
    ListTenantsResult,
    Tenant,
    UserRecord,
} from 'firebase-admin/lib/auth';
import { sign } from 'jsonwebtoken';
import { DateTime } from 'luxon';
import { Document } from 'mongodb';
import { db } from '../src';
import { CollectionEnum } from '../src/enums/CollectionEnum';
import { getAllResources } from '../src/enums/ResourceEnum';
import GetUsersResult = auth.GetUsersResult;
import { ITenant } from '../src/tenants/TenantService';

function generateUsageStatsRow(
    start: DateTime,
    end: DateTime,
    appId = 'neco1',
    appName = 'neco1',
    endUserId = '1235',
    instanceId = 'inst1234',
    installId = 'i1235',
    tenantId = 't123456789',
    cost = 100000,
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
    };
}

export function generateAuth(): Auth {
    return {} as Auth;
}

export function generateTenantMockedData(name = 'neco'): Tenant {
    return {
        tenantId: 't-123456789',
        displayName: name || null,
        emailSignInConfig: {
            enabled: true,
            passwordRequired: true,
        },
        anonymousSignInEnabled: false,
    } as Tenant;
}

export function generateDbTenantMockedData(tenantId = ''): ITenant {
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
        await db.getCloudCollection(CollectionEnum.TENANT).drop();
    }
    await db.getCloudCollection(CollectionEnum.TENANT).insertOne(generateDbTenantMockedData(tenantId));
}

export async function createUsageStats(): Promise<void> {
    await db.getBillingCollection(CollectionEnum.USAGE_STATS_MONTHLY)
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

    await db.getBillingCollection(CollectionEnum.USAGE_STATS_MONTHLY)
        .insertMany([
            generateUsageStatsRow(startDate1, endDate1, 'neco', 'neco', '1235', 'inst1234', 'i1234', 't123456789', 500000),
            generateUsageStatsRow(startDate2, endDate2, 'neco', 'neco', '1235', 'inst1234', 'i1234', 't123456789', 1000000),
            generateUsageStatsRow(startDate3, endDate3, 'neco', 'neco', '1235', 'inst1234', 'i1234', 't123456789', 1000000),
            generateUsageStatsRow(startDate2, endDate2),
            generateUsageStatsRow(startDate3, endDate3),
            generateUsageStatsRow(startDate3, endDate3, 'neco1', 'neco1', '1234', 'inst1234', 'i1236'),
            generateUsageStatsRow(startDate3, endDate3, 'neco1', 'neco1', '1234', 'inst1234', 'i1236', 't123'),
            generateUsageStatsRow(startDate1, endDate1, 'neco', 'neco', '1235', 'inst1235', 'i1237', 't123456789', 500000),
            generateUsageStatsRow(startDate2, endDate2, 'neco', 'neco', '1235', 'inst1235', 'i1237', 't123456789', 1000000),
            generateUsageStatsRow(startDate3, endDate3, 'neco', 'neco', '1235', 'inst1235', 'i1237', 't123456789', 1000000),
            generateUsageStatsRow(startDate2, endDate2, 'neco1', 'neco1', '1235', 'inst1235', 'i1238'),
            generateUsageStatsRow(startDate3, endDate3, 'neco1', 'neco1', '1235', 'inst1235', 'i1238'),
            generateUsageStatsRow(startDate3, endDate3, 'neco1', 'neco1', '1234', 'inst1235', 'i1239'),
            generateUsageStatsRow(startDate3, endDate3, 'neco1', 'neco1', '1234', 'inst1235', 'i1239', 't123'),
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
