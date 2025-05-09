import { readFileSync } from 'fs';

const devIp = (/(?<temp2>DEV_IP=)(?<temp1>.*)/.exec(readFileSync(`${__dirname}/../.env`)?.toString()))?.[2] ?? '';

process.env.NODE_ENV = 'prod' // 'debug' <= use it if you want to see more logs

if (process.env.JEST_DOCKER) {
    // --- DOCKER ---
    process.env.MONGODB_DSN = 'mongodb://mongodb'
} else {
    // --- LOCALHOST ---
    process.env.MONGODB_DSN = `mongodb://${devIp}:27017`
}
