import { getEnv } from '@orchesty/nodejs-sdk/dist/lib/Config/Config';

const redis = {
    port: 6379,
    host: getEnv('REDIS_HOST'),
};

export default {
    redis,
};
