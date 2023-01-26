import { Db, MongoClient } from 'mongodb';
import { mongo } from '../config/config';
import { logger } from '../logger/logger';

export default class Storage {

    private usdb: Db | undefined = undefined;

    private client: MongoClient | undefined = undefined;

    public async init(): Promise<void> {
        logger.info('Connecting to MongoDB... ');
        this.client = new MongoClient(mongo.dsn, {
            maxPoolSize: 5,
            heartbeatFrequencyMS: 5000,
        });

        await this.client.connect();
        this.usdb = this.client.db();
    }

    public getUSDb(): Db {
        if (this.usdb !== undefined) {
            return this.usdb;
        }
        throw new Error('usdb instance not initialized');
    }

    public async disconnect(): Promise<void> {
        if (this.client !== undefined) {
            await this.client.close();
        }
    }

}
