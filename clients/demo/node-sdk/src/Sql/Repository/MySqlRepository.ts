import { AbstractRepository } from '@orchesty/nodejs-sdk/dist/lib/Storage/Mongo';
import { IndexDescription } from 'mongodb';
import { MySqlDocument } from '../Entity/MySqlDocument';

export default class MySqlRepository extends AbstractRepository<MySqlDocument> {

    protected readonly indices: IndexDescription[] = [
        {
            name: 'mysqlUniqueIndex',
            unique: true,
            key: {
                type: 1,
                entityId: 1,
                externalId: 1,
            },
        },
    ];

}
