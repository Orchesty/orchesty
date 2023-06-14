import assert from 'assert';
import { Express } from 'express';
import supertest from 'supertest';
import Services from '../../base/DIContainer/Services';
import { container } from '../../index';

describe('statusController', () => {
    it('shouldReturn200', async () => {
        const resp = await supertest(container.get<Express>(Services.SERVER)).get('/status');
        assert.deepEqual(resp.statusCode, 200);
    });
});
