import axios from 'axios';
import MockAdapter from 'axios-mock-adapter';
import { createServer, db, initServices } from '../src';
import { generateAuth } from '../test/dataProvider';

beforeAll(async () => {
    await initServices();
    createServer();
});

afterAll(async () => {
    await db.disconnect();
});

jest.mock('firebase/auth', () => ({
    getAuth: jest.fn().mockReturnValue(() => generateAuth()),
    sendPasswordResetEmail: jest.fn().mockReturnValue(Promise.resolve()),
}));

export const mockAdapter = new MockAdapter(axios);
