import { getEnv } from '@orchesty/nodejs-sdk/dist/lib/Config/Config';

const mongo = {
    dsn: getEnv('MONGODB_DSN'),
};

export default {
    mongo,
};
