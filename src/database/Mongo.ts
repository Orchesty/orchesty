import { Collection, MongoClient } from 'mongodb';
import { mongoOptions } from '../config/Config';
import DocumentEnum from '../enum/DocumentEnum';
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

            const result = await this.client.db().command({ ping: 1 }) as { ok: number };
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
        return this.client.db().collection(DocumentEnum.API_TOKEN);
    }

    public getCollection(collectionName: string): Collection {
        return this.client.db().collection(collectionName);
    }

    public getMetricsCollection(collectionName: string): Collection {
        return this.client.db(mongoOptions.metricsDb).collection(collectionName);
    }

    public async ensureMetricsIndexes(): Promise<void> {
        const indexSpecs: Record<string, { key: Record<string, 1 | -1>; name: string }[]> = {
            pipes_node: [
                { key: { 'tags.correlation_id': 1 }, name: 'tags_correlation_id_1' },
            ],
            connectors: [
                { key: { 'tags.correlation_id': 1 }, name: 'tags_correlation_id_1' },
            ],
            monolith: [
                { key: { 'tags.correlation_id': 1 }, name: 'tags_correlation_id_1' },
            ],
        };

        for (const [collection, specs] of Object.entries(indexSpecs)) {
            try {
                const coll = this.getMetricsCollection(collection);
                // eslint-disable-next-line no-await-in-loop
                await coll.createIndexes(
                    specs.map((s) => ({ key: s.key, name: s.name, background: true })),
                );
            } catch (e) {
                logger.error(`Failed to ensure indexes for metrics.${collection}: [${e}]`);
            }
        }
    }

    public async dropCollections(): Promise<void> {
        for (const collection of Object.values(DocumentEnum)) {
            try {
                // eslint-disable-next-line no-await-in-loop
                await this.client.db().dropCollection(collection);
            } catch (e) {
                logger.error(`Mongo error: [${e}]`);
            }
        }
    }

}
