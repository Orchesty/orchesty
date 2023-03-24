import axios from 'axios';
import MockAdapter from 'axios-mock-adapter';
import {container, createServer, initServices} from '../src';
import {generateAuth} from '../test/dataProvider';
import Mongo from '../src/storage/mongo/Mongo';
import Services from '../src/DIContainer/Services';
import {CollectionEnum} from "../src/enums/CollectionEnum";

beforeEach(async () => {
    const storage = container.get<Mongo>(Services.STORAGE)
    try {
        await storage.getCloudCollection(CollectionEnum.ADDRESS).drop();
    } catch (e) {
    }
    try {
        await storage.getCloudCollection(CollectionEnum.APPLINTH).drop();
    } catch (e) {
    }
    try {
        await storage.getCloudCollection(CollectionEnum.CLIENT).drop();
    } catch (e) {
    }
    try {
        await storage.getCloudCollection(CollectionEnum.CLOUD).drop();
    } catch (e) {
    }
    try {
        await storage.getCloudCollection(CollectionEnum.CORRECTION).drop();
    } catch (e) {
    }
    try {
        await storage.getCloudCollection(CollectionEnum.MODULE).drop();
    } catch (e) {
    }
    try {
        await storage.getCloudCollection(CollectionEnum.ORCHESTY).drop();
    } catch (e) {
    }
});

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
