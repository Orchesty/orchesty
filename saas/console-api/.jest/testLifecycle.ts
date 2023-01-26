import axios from 'axios';
import MockAdapter from 'axios-mock-adapter';
import {container, createServer, initServices} from '../src';
import {generateAuth} from '../test/dataProvider';
import Mongo from "../src/storage/mongo/Mongo";
import Services from "../src/DIContainer/Services";

beforeAll(async () => {
    await initServices();
    createServer();
});

afterAll(async () => {
    await container.get<Mongo>(Services.STORAGE).disconnect();
});

jest.mock('firebase/auth', () => ({
    getAuth: jest.fn().mockReturnValue(() => generateAuth()),
    sendPasswordResetEmail: jest.fn().mockReturnValue(Promise.resolve()),
}));

export const mockAdapter = new MockAdapter(axios);
