import * as process from 'process';

process.env.NODE_ENV = 'prod' // 'debug' <= use it if you want to see more logs

if (process.env.JEST_DOCKER) {
    // --- DOCKER ---
    process.env.MONGODB_DSN = 'mongodb://mongodb'
} else {
    // --- LOCALHOST ---
    process.env.MONGODB_DSN = 'mongodb://127.0.0.43:27017'
}
