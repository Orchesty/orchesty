import { ObjectId } from 'mongodb';

export default interface BaseEntity {
    _id?: ObjectId | string;
    deleted?: Date | null;
}
