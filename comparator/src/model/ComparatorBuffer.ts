import { ObjectId } from 'mongodb';

export class ComparatorBuffer {

    public _id: ObjectId = new ObjectId();

    public ttl: Date = new Date();

    public pages: string[] = [];

    public constructor(
        public key: string,
        public data: Record<string, unknown>[],
        public closed: boolean,
    ) {
    }

}
