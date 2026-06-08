import axios from 'axios';

const PUSH_PATH = '/loki/api/v1/push';
const MAX_BATCH = 500;
const QUEUE_SIZE = 16_384;
const FLUSH_INTERVAL_MS = 2_000;

interface IEntry {
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

        this.queue.push({ labels, line, tsNs: `${Date.now()}000000` });

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

        for (let start = 0; start < entries.length; start += MAX_BATCH) {
            // eslint-disable-next-line no-await-in-loop
            await this.post(Loki.buildBatchBody(entries.slice(start, start + MAX_BATCH)));
        }
    }

    private async post(body: { streams: IStream[] }): Promise<void> {
        try {
            await axios.post(`${this.dsn}${PUSH_PATH}`, body, {
                headers: {
                    'Content-Type': 'application/json',
                },
                timeout: 5_000,
            });
        } catch {
            // Intentionally
        }
    }

}
