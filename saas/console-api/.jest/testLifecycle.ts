import {db, initServices, createServer} from '../src';
import {generateAuth} from '../test/dataProvider';
import {FetchMockStatic} from "fetch-mock";
import mf from 'node-fetch';

beforeAll(async () => {
    await initServices();
    createServer();
})

afterAll(async () => {
    await db.disconnect();
})

jest.mock('node-fetch', () => require('fetch-mock-jest').sandbox());

jest.mock('firebase/auth', () => ({
    getAuth: jest.fn().mockReturnValue(() => generateAuth()),
    sendPasswordResetEmail: jest.fn().mockReturnValue(Promise.resolve()),
}));

export const fetchMock: FetchMockStatic = mf as unknown as FetchMockStatic;
