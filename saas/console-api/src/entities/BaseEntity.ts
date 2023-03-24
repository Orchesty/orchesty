import { ObjectId } from 'mongodb';

export default interface BaseEntity {
    _id: ObjectId | string;
    created?: Date | null;
    updated?: Date | null;
    deleted?: Date | null;
}
