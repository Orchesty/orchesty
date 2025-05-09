import { readFileSync } from 'fs';

const devIp = (/(?<temp2>DEV_IP=)(?<temp1>.*)/.exec(readFileSync(`${__dirname}/../.env`)?.toString()))?.[2] ?? '';

// --- COMMONS ---

process.env.APP_ENV = 'debug'
process.env.NODE_ENV = 'debug'

if (process.env.JEST_DOCKER) {
  // --- DOCKER ---
  process.env.MONGODB_DSN = 'mongodb://mongo'
} else {
  // --- LOCALHOST ---
  process.env.MONGODB_DSN = `mongodb://${devIp}:27017`
}

jest.setTimeout(10000);
