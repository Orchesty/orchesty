import { container } from '@orchesty/nodejs-sdk';
import { MongoDb } from '@orchesty/nodejs-sdk/dist/lib/Storage/Mongo';
import { initialize } from '../src';
import {
    ComparatorBufferRepository,
    ComparatorHashRepository,
    ComparatorLockRepository,
} from '../src/service/storage/repository';

jest.setTimeout(10000);

beforeAll(async function() {
    await initialize();
});

afterAll(async function() {
    await container.get(MongoDb).disconnect();
});

beforeEach(async function() {
    await container.get(ComparatorLockRepository).deleteAll();
    await container.get(ComparatorHashRepository).deleteAll();
    await container.get(ComparatorBufferRepository).deleteAll();
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
