// --- COMMONS ---
import * as process from 'process';

process.env.APP_ENV = 'debug'

if (process.env.JEST_DOCKER) {
  // --- DOCKER ---
  process.env.MONGODB_DSN = 'mongodb://mongo'
} else {
  // --- LOCALHOST ---
  process.env.MONGODB_DSN = 'mongodb://127.0.0.42:27017'
}

jest.setTimeout(10000);
