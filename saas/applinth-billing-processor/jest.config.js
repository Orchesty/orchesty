module.exports = {
  preset: 'ts-jest',
  testEnvironment: 'node',
  testMatch: ['<rootDir>/test/**/*.test.ts'],
  roots: ["<rootDir>/src/", "<rootDir>/test/"],
  setupFiles: ["<rootDir>/.jest/testEnvs.ts"],
  globalSetup: '<rootDir>/.jest/globalSetup.ts',
  setupFilesAfterEnv: ["<rootDir>/.jest/testLifecycle.ts"],
};
