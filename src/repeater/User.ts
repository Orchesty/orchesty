import * as mongoose from "mongoose";

export interface IUser extends mongoose.Document {
    name: string;
    somethingElse?: number;
}

export const UserSchema = new mongoose.Schema({
    name: {type: String, required: true},
    somethingElse: Number,
});

const User = mongoose.model<IUser>("repeater", UserSchema);

export default User;
