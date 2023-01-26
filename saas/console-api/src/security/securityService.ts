import { Request } from 'express';
import { decode } from 'jsonwebtoken';
import { app } from '../config/config';
import Services from '../DIContainer/Services';
import { CollectionEnum } from '../enums/CollectionEnum';
import { ResourceEnum } from '../enums/ResourceEnum';
import JWTError, { BAD_JWT_PAYLOAD, ERROR_PARSING_AUTHORIZATION_HEADER, MISSING_JWT_TOKEN } from '../errors/JWTError';
import PermissionsError from '../errors/PermissionsError';
import TenantSearchError from '../errors/TenantSearchError';
import { container } from '../index';
import Mongo from '../storage/mongo/Mongo';
import { ITenant } from '../tenants/TenantService';

export const AUTHORIZATION = 'authorization';
export const X_ENDPOINT_API_USER_INFO = 'x-endpoint-api-userinfo';

export interface IJWTPayload {
    /* eslint-disable @typescript-eslint/naming-convention */
    firebase: { tenant: string };
    first_name: string;
    last_name: string;
    email: string;
    permissions: string[];
    /* eslint-enable @typescript-eslint/naming-convention */
}

export function getJWTPayload(req: Request): IJWTPayload {
    // Set by ESPv2
    // https://cloud.google.com/endpoints/docs/openapi/authenticating-users-custom#receiving_authenticated_results_in_your_api
    const userInfo = req.get(X_ENDPOINT_API_USER_INFO);
    if (userInfo) {
        const buff = Buffer.from(userInfo, 'base64');
        return JSON.parse(buff.toString()) as IJWTPayload;
    }

    const authorization = req.get(AUTHORIZATION);
    if (authorization) {
        const match = (/^Bearer (?<jwt>.+)/).exec(authorization);
        if (!match?.groups) {
            throw new JWTError(ERROR_PARSING_AUTHORIZATION_HEADER);
        }

        return decode(match.groups.jwt) as IJWTPayload;
    }

    if (app.debug) {
        return {
            /* eslint-disable @typescript-eslint/naming-convention */
            firebase: { tenant: 'hanaboso' },
            first_name: 'John',
            last_name: 'Doe',
            email: 'test@example.com',
            permissions: [],
            /* eslint-enable @typescript-eslint/naming-convention */
        };
    }

    throw new JWTError(MISSING_JWT_TOKEN);
}

export async function getLoggedUser(req: Request): Promise<ITenant> {
    const jwtPayload = getJWTPayload(req);
    if (jwtPayload.firebase?.tenant) {
        const db = container.get<Mongo>(Services.STORAGE);
        return await db.getCloudCollection(CollectionEnum.TENANT).findOne({
            gTenantId: jwtPayload.firebase.tenant,
        }) as unknown as ITenant;
    }

    throw new JWTError(BAD_JWT_PAYLOAD);
}

export function getLoggedUserPermissions(req: Request): string[] {
    const jwtPayload = getJWTPayload(req);
    return jwtPayload.permissions ?? [];
}

// eslint-disable-next-line @typescript-eslint/no-unused-vars
export function hasPermission(permissions: string[], resource: string): boolean {
    // TODO temporary disabled
    // return permissions.includes(resource);

    return true;
}

export async function preprocessRequest<IQuery extends { tenantId?: string; gTenantId?: string }>(
    req: Request,
    permission: ResourceEnum,
): Promise<{ query: IQuery; tenantId: string; gTenantId: string }> {
    const tenant = await getLoggedUser(req);
    const query: IQuery = { ...req.query, ...req.params } as unknown as IQuery;
    const permissions = getLoggedUserPermissions(req);

    let allowed = true;

    if (!hasPermission(permissions, permission)) {
        allowed = false;
    } else if (query.tenantId && tenant && query.tenantId !== tenant.tenantId) {
        allowed = hasPermission(permissions, ResourceEnum.USE_ANOTHER_TENANT_ID);
    }

    if (!allowed) {
        throw new PermissionsError();
    }

    if (permission === ResourceEnum.DELETE_TENANT && tenant && query.tenantId === tenant.tenantId) {
        throw new PermissionsError('Tenant cannot delete himself!');
    }

    if (query.tenantId) {
        const db = container.get<Mongo>(Services.STORAGE);
        const queriedTenant = await db.getCloudCollection(CollectionEnum.TENANT).findOne<ITenant>({
            tenantId: query.tenantId,
        });

        if (queriedTenant) {
            query.tenantId = queriedTenant.tenantId;
            query.gTenantId = queriedTenant.gTenantId;
        } else {
            throw new TenantSearchError(`Tenant with tenantId ${query.tenantId} not found!`);
        }
    }

    return { query, tenantId: tenant.tenantId, gTenantId: tenant.gTenantId };
}
