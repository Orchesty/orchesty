import { Collection, MongoClient } from 'mongodb';

export default class Mongo {

    protected readonly dbName: string | undefined = undefined;

    protected readonly client: MongoClient;

    public constructor(dsn: string) {
        this.client = new MongoClient(dsn);
    }

    public async connect(): Promise<MongoClient> {
        return this.client.connect();
    }

    public async disconnect(): Promise<void> {
        return this.client.close();
    }

    public getCollection(collection: string): Collection {
        return this.client.db(this.dbName)
            .collection(collection);
    }

}
