import assert from 'assert';
import { Request } from 'express';
import { AUTHORIZATION, getJWTPayload, getLoggedUser, getLoggedUserPermissions, X_ENDPOINT_API_USER_INFO } from '../securityService';

const validPayload = {
    /* eslint-disable @typescript-eslint/naming-convention */
    sub: '1234567890',
    iat: 1516239022,
    first_name: 'John',
    last_name: 'Doe',
    email: 'nic@nebu.de',
    firebase: {
        tenant: 'abcde-fghij',
    },
    permissions: ['foo'],
    /* eslint-enable @typescript-eslint/naming-convention */
};

const validJWTToken
    = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwiaWF0Ijox'
    + 'NTE2MjM5MDIyLCJmaXJzdF9uYW1lIjoiSm9obiIsImxhc3RfbmFtZSI6IkRvZSIsImVtYWlsI'
    + 'joibmljQG5lYnUuZGUiLCJmaXJlYmFzZSI6eyJ0ZW5hbnQiOiJhYmNkZS1mZ2hpaiJ9LCJwZX'
    + 'JtaXNzaW9ucyI6W119.qkzsjrkKi-QO-eBeJlXh_Hj5ZpMMx3ruvlvYBf6agWc';

const validMockRequest = {
    get(name) {
        if (name === X_ENDPOINT_API_USER_INFO) {
            const buff = Buffer.from(JSON.stringify(validPayload));
            return buff.toString('base64');
        }
        return undefined;
    },
} as Request;

describe('getJWTPayload with X_ENDPOINT_API_USER_INFO', () => {
    it('should return payload object', () => {
        const res = getJWTPayload(validMockRequest);
        assert.equal(res.email, validPayload.email);
    });
});

describe('getJWTPayload with AUTHORIZATION', () => {
    it('should return payload object', () => {
        const mockRequest = {
            get(name) {
                if (name === AUTHORIZATION) {
                    return `Bearer ${validJWTToken}`;
                }
                return undefined;
            },
        } as Request;
        const res = getJWTPayload(mockRequest);
        assert.equal(res.email, validPayload.email);
    });
});

describe('getLoggedUser', () => {
    it('should return tenant id', () => {
        const tenant = getLoggedUser(validMockRequest);
        assert.equal(tenant, validPayload.firebase.tenant);
    });
});

describe('getLoggedUserPermissions', () => {
    it('should return array of permissions', () => {
        const permissions = getLoggedUserPermissions(validMockRequest);
        assert.deepEqual(permissions, validPayload.permissions);
    });
});
