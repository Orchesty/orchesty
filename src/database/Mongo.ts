import { Collection, MongoClient } from 'mongodb';
import { mongoOptions } from '../config/Config';
import { logger } from '../logger/Logger';

export default class Mongo {

    protected readonly client: MongoClient;

    public constructor() {
        this.client = new MongoClient(mongoOptions.mongoDsn);
    }

    public async connect(): Promise<MongoClient> {
        return this.client.connect();
    }

    public async disconnect(): Promise<void> {
        return this.client.close();
    }

    public async isConnected(): Promise<boolean> {
        const client = new MongoClient(
            mongoOptions.mongoDsn,
            {
                serverSelectionTimeoutMS: 5000,
            },
        );

        try {
            await client.connect();
            await client.close();

            const result = await this.client.db().command({ ping: 1 }, { maxTimeMS: 5000 }) as { ok: number };
            if (result.ok) {
                return true;
            }
            logger.error(`Mongo response: [${JSON.stringify(result ?? '')}]`);
            return false;
        } catch (e) {
            logger.error(`Mongo error: [${e}]`);
            return false;
        }
    }

    public getApiKeyCollection(): Collection {
        return this.client.db().collection(mongoOptions.collections.apiToken);
    }

    public getMeasurementCollection(measurement: string): Collection {
        return this.client.db().collection(measurement);
    }

    public async dropCollections(): Promise<void> {
        for (const collection of Object.values(mongoOptions.collections)) {
            try {
                // eslint-disable-next-line no-await-in-loop
                await this.client.db().dropCollection(collection);
            } catch (e) {
            }
        }
    }

}
