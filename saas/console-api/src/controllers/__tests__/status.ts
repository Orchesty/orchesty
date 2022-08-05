import supertest from 'supertest';
import assert from 'assert';
import { server } from '../../index';

describe('statusController', () => {
  it('shouldReturn200', async () => {
    const resp = await supertest(server).get('/status');
    assert.deepEqual(resp.statusCode, 200);
  });
});
