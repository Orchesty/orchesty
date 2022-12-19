// --- COMMONS ---
process.env.APP_ENV = 'debug'

if (process.env.JEST_DOCKER) {
  // --- DOCKER ---
  process.env.MONGO_DSN = 'mongodb://mongo:27017/worker-api'
} else {
  // --- LOCALHOST ---
  process.env.MONGO_DSN = 'mongodb://127.0.0.87:27017/worker-api'
}
