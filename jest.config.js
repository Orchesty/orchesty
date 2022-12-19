module.exports = {
  preset: 'ts-jest',
  testEnvironment: 'node',
  testMatch: ['**/__tests__/*.ts'],
  roots: ["<rootDir>/src/"],
  setupFiles: ["<rootDir>/.jest/testEnvs.js"],
};
