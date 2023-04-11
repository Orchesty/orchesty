import { MongoClient } from 'mongodb';
import { logger } from '../main';

export class MongoDBConn {

    public readonly ready: Promise<MongoClient>;

    protected client: MongoClient | null = null;

    // TODO rich prepsat na reseni jako je v console-api a nebo USCPP
    public constructor(url: string) {
        this.ready = new Promise<MongoClient>((resolve, reject): void => {
            (async () => {
                logger.info('Connecting to MongoDB...');
                this.client = new MongoClient(url, {
                    maxPoolSize: 5,
                    heartbeatFrequencyMS: 5000,
                });

                try {
                    await this.client.connect();
                    await this.client.db('admin').command({ ping: 1 });
                    logger.info('MongoDB connected!');
                    resolve(this.client);
                } catch (e) {
                    reject(e);
                }
            })().catch((e) => {
                throw e;
            });
        });
    }

    public async close(): Promise<void> {
        if (this.client) {
            const { client } = this;
            this.client = null;
            await client.close();
        }
    }

}
