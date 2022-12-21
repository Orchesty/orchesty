// --- COMMONS ---
process.env.APP_ENV = 'debug'

if (process.env.JEST_DOCKER) {
  // --- DOCKER ---
  process.env.MONGODB_DSN = 'mongodb://mongo:27017/worker-api'
} else {
  // --- LOCALHOST ---
  process.env.MONGODB_DSN = 'mongodb://127.0.0.99:27017/worker-api'
}
