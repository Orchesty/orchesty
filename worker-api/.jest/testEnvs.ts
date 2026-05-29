import { readFileSync } from 'fs';
const devIp = readFileSync( __dirname + '/../.env')?.toString()?.match("(DEV_IP=)(.*)")?.[2] ?? '';

// --- COMMONS ---
process.env.APP_ENV = 'prod' // 'debug' <= use it if you want to see more logs

if (process.env.JEST_DOCKER) {
  // --- DOCKER ---
  process.env.MONGODB_DSN = 'mongodb://mongo:27017/worker-api';
  process.env.METRICS_DB = 'worker-api-metrics';
} else {
  // --- LOCALHOST ---
  process.env.MONGODB_DSN = `mongodb://${devIp}:27017/worker-api`;
  process.env.METRICS_DB = 'worker-api-metrics';
}
