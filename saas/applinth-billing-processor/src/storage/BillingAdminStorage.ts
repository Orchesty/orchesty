import { Db } from 'mongodb';
import { Config } from '../config';
import { MongoDBConn } from './MongoDBConn';

export class BillingAdminStorage {

    public constructor(
        private readonly conn: MongoDBConn,
        private readonly config: Config,
    ) {
    }

    public async db(): Promise<Db> {
        return (await this.conn.ready).db(this.config.billingAdminDb);
    }

}
