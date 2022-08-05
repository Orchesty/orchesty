import { Collection, MongoClient } from 'mongodb';
import { CollectionEnum } from '../../enums/CollectionEnum';

export default class Mongo {
  private _client: MongoClient;

  constructor(dsn: string) {
    this._client = new MongoClient(dsn);
  }

  public async connect(): Promise<MongoClient> {
    return this._client.connect();
  }

  public async disconnect(): Promise<void> {
    return this._client.close();
  }

  public getCollection(collection: string): Collection {
    return this._client.db()
      .collection(collection);
  }

  public async createIndexes(): Promise<void> {
    const specs = [
      { key: { start: 1 } },
      { key: { end: 1 } },
      { key: { tenantId: 1 } },
      { key: { endUserId: 1 } },
      { key: { endUserDisplayId: 1 } },
      { key: { appName: 1 } },
    ];
    await this.getCollection(CollectionEnum.USAGE_STATS_HOURLY).createIndexes(specs);
    await this.getCollection(CollectionEnum.USAGE_STATS_DAILY).createIndexes(specs);
    await this.getCollection(CollectionEnum.USAGE_STATS_MONTHLY).createIndexes(specs);
  }
}
