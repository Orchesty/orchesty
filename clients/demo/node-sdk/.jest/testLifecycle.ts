import {
    createLoggerMockedServer,
    createMetricsMockedServer,
} from '@orchesty/nodejs-sdk/dist/test/MockServer';

jest.setTimeout(10_000);

beforeAll(async () => {
    createMetricsMockedServer();
    createLoggerMockedServer();
});

