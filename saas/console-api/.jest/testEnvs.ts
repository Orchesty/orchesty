// --- COMMONS ---
import * as process from 'process';

process.env.NODE_ENV = 'prod' // 'debug' <= use it if you want to see more logs

if (process.env.JEST_DOCKER) {
    // --- DOCKER ---
    process.env.MONGODB_DSN = 'mongodb://mongo'
} else {
    // --- LOCALHOST ---
    process.env.MONGODB_DSN = 'mongodb://127.0.0.42:27017'
}

process.env.MONGODB_CLOUDDB_NAME = `cloud${process.env.JEST_WORKER_ID ?? 0}`
process.env.MONGODB_BILLINGDB_NAME = `billing${process.env.JEST_WORKER_ID ?? 0}`
process.env.USCCP_URI = 'http://usscp'
process.env.CLOUD_CONTROLLER_URI = 'http://cloud_controller'
process.env.CLOUD_BRIDGE = 'false'

jest.setTimeout(10000);
