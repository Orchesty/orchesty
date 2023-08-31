// --- COMMONS ---
process.env.APP_ENV = 'debug';

if (process.env.JEST_DOCKER) {
  // --- DOCKER ---
  process.env.MONGODB_DSN = 'mongodb://mongo:27017/worker-api';
  process.env.FLUENTD_DSN = 'fluentd:9880';
} else {
  // --- LOCALHOST ---
  process.env.MONGODB_DSN = 'mongodb://127.0.0.99:27017/worker-api';
  process.env.FLUENTD_DSN = '127.0.0.99:9880';
}
