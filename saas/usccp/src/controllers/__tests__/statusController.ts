import assert from 'assert';
import { Express } from 'express';
import supertest from 'supertest';
import Services from '../../DIContainer/Services';
import { container } from '../../index';

describe('statusController', () => {
    it('shouldReturn200', async () => {
        const server = container.get<Express>(Services.SERVER);
        const resp = await supertest(server).get('/status');
        assert.deepEqual(resp.statusCode, 200);
    });
});
