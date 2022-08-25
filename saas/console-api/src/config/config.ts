import { Settings } from 'luxon';

export const mongo = {
    dsn: process.env.MONGODB_DSN ?? '',
};

export const app = {
    debug: process.env.NODE_ENV === 'debug',
    env: process.env.NODE_ENV,
    port: process.env.APP_PORT ?? 3000,
    openapiPath: './openapi.yaml',
};

export const firebase = {
    apiKey: process.env.FIREBASE_API_KEY ?? '',
};

Settings.throwOnInvalid = true;
