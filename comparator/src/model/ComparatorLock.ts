import { ObjectId } from 'mongodb';

export class ComparatorLock {

    public _id: ObjectId = new ObjectId();

    public constructor(
        public masterKey: string,
        public ttl: Date,
    ) {
    }

}
