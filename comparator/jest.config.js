module.exports = {
  preset: 'ts-jest',
  testEnvironment: 'node',
  testMatch: ['**/*.ts'],
  roots: ["<rootDir>/test/"],
  setupFiles: ["<rootDir>/.jest/testEnvs.js"],
  setupFilesAfterEnv: ["<rootDir>/.jest/testLifecycle.ts"],
  globalSetup: '<rootDir>/.jest/globalSetup.ts',
};
