import { Request } from 'express';
import { decode } from 'jsonwebtoken';
import { app } from '../config/config';
import JWTError, { BAD_JWT_PAYLOAD, ERROR_PARSING_AUTHORIZATION_HEADER, MISSING_JWT_TOKEN } from '../errors/JWTError';

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

export function getLoggedUser(req: Request): string {
    const jwtPayload = getJWTPayload(req);
    if (jwtPayload.firebase?.tenant) {
        return jwtPayload.firebase.tenant;
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
