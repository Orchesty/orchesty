import * as mongoose from "mongoose";
import {Schema} from "mongoose";

export interface IPersistedMessageSchema {
    // message fields
    properties: any;
    fields: any;
    content: any;
    // custom fields
    id: string;
    repeat_interval: number;
    repeat_at: number;
    repeat_at_timestamp: number;
    created_at: number;
}

const schema = new Schema({
    properties: Object,
    fields: Object,
    content: Buffer,
    repeat_interval: Number,
    repeat_at: Date,
    repeat_at_timestamp: Number,
    created_at: Date,
});

export const PersistedMessage = mongoose.model("Message", schema);
