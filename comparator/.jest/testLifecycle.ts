import { container } from '@orchesty/nodejs-sdk';
import { initialize, REDIS_SERVICE_NAME } from '../src';
import Redis from "ioredis";

jest.setTimeout(10000);

beforeAll(async function() {
    await initialize();
});

afterAll(async function() {
    const redis: Redis = container.getNamed(REDIS_SERVICE_NAME);
    redis.disconnect();
});

beforeEach(async function() {
    const redis: Redis = container.getNamed(REDIS_SERVICE_NAME);
    await redis.flushall();
});

export default class MockDate extends Date {
    constructor() {
        super("2022-01-01T10:10:10.000Z");
    }
}

const originalDate = global.Date;

export function mockDate() {
    // @ts-ignore
    global.Date = MockDate;
}

export function restoreDate() {
    global.Date = originalDate;
}
