import { container, createServer, initServices } from '../src';
import { EVENTS_COLLECTION_NAME } from '../src/events/EventService';
import { dropCollection } from '../test/TestAbstract';
import Storage from '../src/storage/Storage';
import Services from '../src/DIContainer/Services';

beforeAll(async () => {
    await initServices();
    await dropCollection(EVENTS_COLLECTION_NAME);
    createServer();
});

afterAll(async () => {
    const storage = container.get<Storage>(Services.STORAGE);
    await storage.disconnect();
});
