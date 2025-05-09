// --- COMMONS ---
process.env.APP_ENV = 'debug'
process.env.CRYPT_SECRET = 'ThisIsNotSoSecret';
process.env.BACKEND_URL = 'http://backend-url';
process.env.TENANT_ID = 'test-tenant';

if (process.env.JEST_DOCKER) {
  // --- DOCKER ---
  process.env.REDIS_HOST = 'redis'
} else {
  // --- LOCALHOST ---
  process.env.REDIS_HOST = '127.0.0.1'
}
