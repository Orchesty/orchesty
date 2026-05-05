import { createLoggerMockedServer, createMetricsMockedServer } from '@orchesty/nodejs-sdk/dist/test/MockServer';

jest.setTimeout(10000);

beforeAll(async () => {
  createMetricsMockedServer();
  createLoggerMockedServer();
});

beforeEach(async () => {
});

afterAll(async () => {
});
