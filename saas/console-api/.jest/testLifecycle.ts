import { db, initServices, createServer, dbCloud } from '../src';
import { generateAuth } from '../test/dataProvider';

beforeAll(async () => {
  await initServices();
  createServer();
})

afterAll(async () => {
  await db.disconnect();
  await dbCloud.disconnect();
})

jest.mock('firebase/auth', () => ({
  getAuth: jest.fn().mockReturnValue(() => generateAuth()),
  sendPasswordResetEmail: jest.fn().mockReturnValue(Promise.resolve()),
}));
