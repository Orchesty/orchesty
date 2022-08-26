module.exports = {
  preset: 'ts-jest',
  testEnvironment: 'node',
  testMatch: ['**/__tests__/*.ts'],
  roots: ["<rootDir>/src/", "<rootDir>/test/"],
  globalSetup: '<rootDir>/.jest/globalSetup.ts',
  setupFiles: ["<rootDir>/.jest/testEnvs.ts"],
  setupFilesAfterEnv: ["<rootDir>/.jest/testLifecycle.ts"],
};
