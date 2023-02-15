// --- COMMONS ---
process.env.APP_ENV = 'debug'
process.env.CRYPT_SECRET = 'ThisIsNotSoSecret';
process.env.BACKEND_URL = 'http://backend-url';
process.env.TENANT_ID = 'test-tenant';

if (process.env.JEST_DOCKER) {
  // --- DOCKER ---
  process.env.MONGODB_DSN = 'mongodb://mongodb:27017/node-sdk'
} else {
  // --- LOCALHOST ---
  process.env.MONGODB_DSN = 'mongodb://127.0.0.15:27017/node-sdk'
}
