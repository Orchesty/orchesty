import { ObjectId } from 'mongodb';

export class ComparatorHash {

    public _id: ObjectId = new ObjectId();

    public constructor(
        public masterKey: string,
        public hash: string,
        public externalId: string,
        public ttl: Date | undefined = undefined,
    ) {
    }

}
