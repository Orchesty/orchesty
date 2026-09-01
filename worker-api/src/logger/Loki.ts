import axios from 'axios';
import { promisify } from 'node:util';
import { gzip } from 'node:zlib';

const gzipAsync = promisify(gzip);

const PUSH_PATH = '/loki/api/v1/push';
const MAX_BATCH = 500;
const GZIP_LEVEL = 6;
const MAX_BATCH_BYTES = 3 * 1_024 * 1_024;
const ENTRY_ENVELOPE_BYTES = 32;
const QUEUE_SIZE = 16_384;
const FLUSH_INTERVAL_MS = 2_000;

interface IEntry {
    bytes: number;
    labels: Record<string, string>;
    line: string;
    tsNs: string;
}

interface IStream {
    stream: Record<string, string>;
    values: [string, string][];
}

export default class Loki {

    private queue: IEntry[] = [];

    private timer: NodeJS.Timeout | undefined;

    private flushing: Promise<void> = Promise.resolve();

    public constructor(private readonly dsn: string) {
    }

    public start(): void {
        if (!this.enabled() || this.timer) {
            return;
        }

        this.queue = [];
        this.timer = setInterval(() => this.triggerFlush(), FLUSH_INTERVAL_MS);
        this.timer.unref();
    }

    public enabled(): boolean {
        return this.dsn !== '';
    }

    public sendLog(labels: Record<string, string>, line: string): void {
        if (!this.enabled() || this.queue.length >= QUEUE_SIZE) {
            return;
        }

        this.queue.push({
            bytes: Buffer.byteLength(JSON.stringify(line)) + ENTRY_ENVELOPE_BYTES,
            labels,
            line,
            tsNs: `${Date.now()}000000`,
        });

        if (this.queue.length >= MAX_BATCH) {
            this.triggerFlush();
        }
    }

    public async close(): Promise<void> {
        if (this.timer) {
            clearInterval(this.timer);
            this.timer = undefined;
        }

        this.triggerFlush();
        await this.flushing;
    }

    private static labelKey(stream: Record<string, string>): string {
        return Object.keys(stream)
            .sort()
            .map((key) => `${key}=${stream[key]}`)
            .join('\0');
    }

    private static buildBatches(entries: IEntry[]): IEntry[][] {
        const batches: IEntry[][] = [];
        let batch: IEntry[] = [];
        let bytes = 0;

        for (const entry of entries) {
            if (batch.length > 0 && (batch.length >= MAX_BATCH || bytes + entry.bytes > MAX_BATCH_BYTES)) {
                batches.push(batch);
                batch = [];
                bytes = 0;
            }

            batch.push(entry);
            bytes += entry.bytes;
        }

        if (batch.length > 0) {
            batches.push(batch);
        }

        return batches;
    }

    private static buildBatchBody(entries: IEntry[]): { streams: IStream[] } {
        const groups = new Map<string, IStream>();

        for (const entry of entries) {
            const stream: Record<string, string> = {};

            for (const [key, value] of Object.entries(entry.labels)) {
                if (value !== '') {
                    stream[key] = value;
                }
            }

            const key = Loki.labelKey(stream);
            let group = groups.get(key);

            if (!group) {
                group = { stream, values: [] };
                groups.set(key, group);
            }

            group.values.push([entry.tsNs, entry.line]);
        }

        const streams = [...groups.values()];

        for (const group of streams) {
            group.values.sort((a, b) => a[0].localeCompare(b[0]));
        }

        return { streams };
    }

    private triggerFlush(): void {
        this.flushing = this.flushing.then(async () => this.flush(), async () => this.flush());
    }

    private async flush(): Promise<void> {
        if (this.queue.length === 0) {
            return;
        }

        const entries = this.queue;
        this.queue = [];

        for (const batch of Loki.buildBatches(entries)) {
            // eslint-disable-next-line no-await-in-loop
            await this.post(Loki.buildBatchBody(batch));
        }
    }

    private async post(body: { streams: IStream[] }): Promise<void> {
        try {
            const payload = await gzipAsync(JSON.stringify(body), { level: GZIP_LEVEL });

            await axios.post(`${this.dsn}${PUSH_PATH}`, payload, {
                headers: {
                    'Content-Type': 'application/json',
                    // eslint-disable-next-line @typescript-eslint/naming-convention
                    'Content-Encoding': 'gzip',
                },
                timeout: 5_000,
            });
        } catch {
            // Intentionally
        }
    }

}
