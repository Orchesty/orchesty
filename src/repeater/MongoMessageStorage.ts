import {Message} from "amqplib";
import * as mongoose from "mongoose";
import {Connection} from "mongoose";
import IMessageStorage from "./IMessageStorage";

export interface IMongoMessageStorageSettings {
    host: string;
    port: number;
    user: string;
    pass: string;
    db: string;
}

class MongoMessageStorage implements IMessageStorage {

    private db: Connection;

    /**
     *
     * @param {IMongoMessageStorageSettings} settings
     */
    constructor(private settings: IMongoMessageStorageSettings) {
        this.connect();
    }

    /**
     * Saves message to the storage
     *
     * @param {Message} message
     * @return {Promise<boolean>}
     */
    public save(message: Message): Promise<boolean> {
        return Promise.resolve(true);
    }

    /**
     * Returns messages that should be re-sent
     *
     * @return {Promise<Message[]>}
     */
    public get(): Promise<Message[]> {
        return Promise.resolve([]);
    }

    /**
     * Creates new mongo connection
     */
    private connect() {
        const opts = {
            server: { auto_reconnect: true },
            user: this.settings.user,
            pass: this.settings.pass,
        };
        this.db = mongoose.createConnection(this.settings.host, this.settings.db, this.settings.port, opts);
        this.db.on("error", console.error.bind(console, "connection error:"));
        this.db.once("open", () => { console.log("connected to mongo"); });
    }

}

export default MongoMessageStorage;