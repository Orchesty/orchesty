import { Settings } from 'luxon';

export const mongo = {
    dsn: process.env.MONGODB_DSN ?? '',
};

export const app = {
    debug: process.env.NODE_ENV === 'debug',
    env: process.env.NODE_ENV,
    mongoCloudDbName: process.env.MONGODB_CLOUDDB_NAME ?? 'cloud',
    mongoBillingDbName: process.env.MONGODB_BILLINGDB_NAME ?? 'billing',
    port: process.env.APP_PORT ?? 3000,
    openapiPath: './openapi.yaml',
    corsOrigin: process.env.CORS_ORIGIN ?? '*',
};

export const firebase = {
    apiKey: process.env.FIREBASE_API_KEY ?? '',
};

Settings.throwOnInvalid = true;
