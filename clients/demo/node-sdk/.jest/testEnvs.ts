import { readFileSync } from 'fs';

export const devIp
    = readFileSync(`${__dirname}/../.env`)
        ?.toString()
        ?.match('(DEV_IP=)(.*)')?.[2] ?? '';

// --- COMMONS ---
process.env.APP_ENV = 'prod'; // 'debug' <= use it if you want to see more logs
process.env.CRYPT_SECRET = 'ThisIsNotSoSecret';
process.env.ORCHESTY_API_KEY = 'ThisIsNotSoSecretApiKey';
process.env.BACKEND_URL = `http://${devIp}:8080`;
process.env.STARTING_POINT_URL = `http://${devIp}:8080`;
process.env.WORKER_API_HOST = `http://${devIp}:8080`;
process.env.UDP_LOGGER_DSN = `${devIp}:5005`;
process.env.REDIS_HOST = devIp;
process.env.REDIS_DSN = `redis://${devIp}`;
process.env.MONGO_DSN = `mongodb://${devIp}/node-sdk`;
process.env.METRICS_DSN = `mongodb://${devIp}/metrics`;

if (process.env.JEST_DOCKER) {
    process.env.UDP_LOGGER_DSN = 'logstash:5005'
    process.env.REDIS_HOST = 'redis'
    process.env.REDIS_DSN = 'redis://redis';
    process.env.MONGO_DSN = 'mongodb://mongo/node-sdk';
    process.env.METRICS_DSN = `mongodb://mongo/metrics`;
}
