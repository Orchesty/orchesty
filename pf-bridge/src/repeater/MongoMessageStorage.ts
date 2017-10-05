import {Message} from "amqplib";
import * as mongoose from "mongoose";
import logger from "./../logger/Logger";
import IMessageStorage from "./IMessageStorage";
import {IPersistedMessageSchema, PersistedMessage} from "./model/PersistedMessage";

export interface IMongoMessageStorageSettings {
    host: string;
    port: number;
    user: string;
    pass: string;
    db: string;
}

/**
 * TODO - solve deprecated default promise warning
 * TODO - solve deprecated openUri warning
 */
class MongoMessageStorage implements IMessageStorage {

    private db: any;

    /**
     *
     * @param {IMongoMessageStorageSettings} settings
     */
    constructor(private settings: IMongoMessageStorageSettings) {
        // Promise = global.Promise;
        mongoose.connect(
            `mongodb://${this.settings.host}/${this.settings.db}`,
            {
                useMongoClient: true,
                // autoReconnect: true,
                // reconnectTries: Number.MAX_VALUE,
            },
            () => {
                // connect cb
            },
        );
        // this.connect();
    }

    /**
     * Saves message to the storage
     *
     * @param {Message} message
     * @return {Promise<boolean>}
     */
    public save(message: Message): Promise<boolean> {
        const now = Date.now();
        const repeatInterval = parseInt(message.properties.headers.repeat_interval, 10);
        const repeatAt = now + repeatInterval;

        const document: IPersistedMessageSchema = {
            properties: message.properties,
            fields: message.fields,
            content: message.content,
            id: "",
            repeat_interval: repeatInterval,
            repeat_at: repeatAt,
            repeat_at_timestamp: repeatAt,
            created_at: now,
        };

        const toSave = new PersistedMessage(document);

        return toSave.save()
            .then(() => {
                return true;
            })
            .catch(() => {
                return false;
            });
    }

    /**
     * Returns messages that should be re-sent
     *
     * @return {Promise<Message[]>}
     */
    public findExpired(): Promise<Message[]> {
        return PersistedMessage.find({repeat_at : { $lte : Date.now()} })
            .then((documents: any) => {
                return this.cleanDocuments(documents);
            });
    }

    public findAll() {
        return PersistedMessage.find({});
    }

    /**
     * Use wisely. Clears the whole collection
     * @return {Promise<boolean>}
     */
    public clearAll(): Promise<boolean> {
        return PersistedMessage.remove({})
            .then(() => {
                return true;
            });
    }

    /**
     * Removes documents form mongo and trims the documents to contain only Message's fields
     *
     * @param {IPersistedMessageSchema[]} documents
     * @return {any}
     */
    private cleanDocuments(documents: IPersistedMessageSchema[]): Promise<Message[]> {
        if (documents.length === 0) {
            return Promise.resolve([]);
        }

        const messageProms: Array<Promise<Message>> = [];
        documents.forEach((doc: IPersistedMessageSchema) => {

            const prom = PersistedMessage.findByIdAndRemove(doc.id)
                .then(() => {
                    return {
                        fields: doc.fields || {},
                        properties: doc.properties || {},
                        content: doc.content || new Buffer(""),
                    };
                });

            messageProms.push(prom);
        });

        return Promise.all(messageProms);
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

        this.db.on("error", (err: any) => {
            logger.error("MongoDb connection error.", { node_id: "repeater", error: err });
        });
        this.db.on("close", (err: any) => {
            logger.warn("MongoDb connection closed.", { node_id: "repeater", error: err });
        });
        this.db.on("open", () => {
            logger.info("MongoDb connection opened.", { node_id: "repeater" });
        });
    }

}

export default MongoMessageStorage;
