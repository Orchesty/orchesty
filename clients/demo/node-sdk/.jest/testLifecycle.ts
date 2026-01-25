import logger from '@orchesty/nodejs-sdk/dist/lib/Logger/Logger';
import {
    createLoggerMockedServer,
    createMetricsMockedServer,
} from '@orchesty/nodejs-sdk/dist/test/MockServer';

jest.setTimeout(10_000);

beforeAll(async () => {
    // @ts-expect-error
    logger.logger.level = 'error';
    createMetricsMockedServer();
    createLoggerMockedServer();
});

