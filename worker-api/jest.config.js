module.exports = {
  preset: 'ts-jest',
  testEnvironment: 'node',
  testMatch: ['**/*.test.ts'],
  roots: ["<rootDir>/tests/"],
  setupFiles: ["<rootDir>/.jest/testEnvs.ts"],
  globalSetup: '<rootDir>/.jest/globalSetup.ts',
};
