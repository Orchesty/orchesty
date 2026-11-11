import assert from 'assert';
import axios from 'axios';
import { gunzipSync } from 'node:zlib';
import Loki from '../../src/logger/Loki';

jest.mock('axios');

const post = jest.mocked(axios.post);

const MAX_BATCH_BYTES = 3 * 1_024 * 1_024;

interface IBody {
    streams: { stream: Record<string, string>; values: [string, string][] }[];
}

function payloads(): Buffer[] {
    return post.mock.calls.map((call: unknown[]) => call[1] as Buffer);
}

function bodies(): IBody[] {
    return payloads().map((payload) => JSON.parse(gunzipSync(payload).toString()) as IBody);
}

describe('Tests for loki client', () => {
    beforeEach(() => {
        post.mockReset();
    });

    it('loki - splits batches by size', async () => {
        const loki = new Loki('http://loki');
        const line = 'x'.repeat(500 * 1_024);

        for (let i = 0; i < 10; i++) {
            loki.sendLog({ topologyId: 'topology' }, line);
        }

        await loki.close();

        assert.equal(post.mock.calls.length, 2);
        assert.equal(bodies()[0].streams[0].values.length, 6);
        assert.equal(bodies()[1].streams[0].values.length, 4);

        for (const body of bodies()) {
            assert.ok(Buffer.byteLength(JSON.stringify(body)) < MAX_BATCH_BYTES);
        }
    });

    it('loki - splits batches by size of the escaped line', async () => {
        const loki = new Loki('http://loki');
        const line = '"'.repeat(500 * 1_024);

        for (let i = 0; i < 10; i++) {
            loki.sendLog({ topologyId: 'topology' }, line);
        }

        await loki.close();

        assert.equal(post.mock.calls.length, 4);
        assert.equal(bodies()[0].streams[0].values.length, 3);
        assert.equal(bodies()[3].streams[0].values.length, 1);

        for (const body of bodies()) {
            assert.ok(Buffer.byteLength(JSON.stringify(body)) < MAX_BATCH_BYTES);
        }
    });

    it('loki - splits batches by count', async () => {
        const loki = new Loki('http://loki');

        for (let i = 0; i < 600; i++) {
            loki.sendLog({ topologyId: 'topology' }, `line-${i}`);
        }

        await loki.close();

        assert.equal(post.mock.calls.length, 2);
        assert.equal(bodies()[0].streams[0].values.length, 500);
        assert.equal(bodies()[1].streams[0].values.length, 100);
    });

    it('loki - pushes gzipped', async () => {
        const loki = new Loki('http://loki');
        const line = JSON.stringify({ message: 'Incoming request', data: '"'.repeat(500 * 1_024) });

        loki.sendLog({ levelName: 'info', topologyId: 'topology' }, line);
        await loki.close();

        assert.equal(post.mock.calls.length, 1);
        assert.deepEqual(post.mock.calls[0][2]?.headers, {
            'Content-Type': 'application/json',
            // eslint-disable-next-line @typescript-eslint/naming-convention
            'Content-Encoding': 'gzip',
        });

        const [body] = bodies();
        assert.deepEqual(body.streams[0].stream, { levelName: 'info', topologyId: 'topology' });
        assert.equal(body.streams[0].values[0][1], line);

        const raw = Buffer.byteLength(JSON.stringify(body));
        assert.ok(payloads()[0].length < raw / 4, `${payloads()[0].length} is not much smaller than ${raw}`);
    });

    it('loki - does nothing without dsn', async () => {
        const loki = new Loki('');

        loki.sendLog({ topologyId: 'topology' }, 'line');
        await loki.close();

        assert.equal(post.mock.calls.length, 0);
    });
});
