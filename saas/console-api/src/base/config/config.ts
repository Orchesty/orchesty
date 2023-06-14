import { Settings } from 'luxon';

export const mongo = {
    dsn: process.env.MONGODB_DSN ?? 'mongodb://mongo',
    mongoCloudDbName: process.env.MONGODB_CLOUDDB_NAME ?? 'cloud',
    mongoBillingDbName: process.env.MONGODB_BILLINGDB_NAME ?? 'billing',
};

export const app = {
    usccpUri: process.env.USCCP_URI ?? (() => {
        throw new Error('USCCP_URI not set');
    })(),
    cloudControllerUri: process.env.CLOUD_CONTROLLER_URI ?? (() => {
        throw new Error('CLOUD_CONTROLLER_URI not set');
    })(),
    debug: process.env.NODE_ENV === 'debug',
    env: process.env.NODE_ENV,
    port: process.env.APP_PORT ?? 3000,
    openapiPath: './openapi.yaml',
    corsOrigin: process.env.CORS_ORIGIN ?? '*',
    cloudBridge: process.env.CLOUD_BRIDGE ? process.env.CLOUD_BRIDGE === 'true' : true,
};

export const firebase = {
    apiKey: process.env.FIREBASE_API_KEY ?? '',
    authDomain: process.env.FIREBASE_AUTH_DOMAIN ?? '',
};

Settings.throwOnInvalid = true;
