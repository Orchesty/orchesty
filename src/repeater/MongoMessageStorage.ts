import {Message} from "amqplib";
import {Db, DeleteWriteOpResultObject, MongoClient} from "mongodb";
import logger from "./../logger/Logger";
import IMessageStorage from "./IMessageStorage";

export interface IMongoMessageStorageSettings {
    host: string;
    port: number;
    user: string;
    pass: string;
    db: string;
}

interface IPersistedMessage {
    _id?: string;
    // message fields
    properties: any;
    fields: any;
    content: any;
    // custom fields
    repeat_interval: number;
    repeat_at: number;
    repeat_at_timestamp: number;
    created_at: number;
}

const COLLECTION_NAME = "messages";

class MongoMessageStorage implements IMessageStorage {

    private db: Promise<Db>;

    /**
     *
     * @param {IMongoMessageStorageSettings} settings
     */
    constructor(private settings: IMongoMessageStorageSettings) {
        this.createConnection();
    }

    /**
     * Saves message to the storage
     *
     * @param {Message} message
     * @param {number} timeout
     * @return {Promise<boolean>}
     */
    public save(message: Message, timeout: number): Promise<boolean> {
        const now = Date.now();
        const repeatInterval = timeout;
        const repeatAt = now + repeatInterval;

        const document: IPersistedMessage = {
            properties: message.properties,
            fields: message.fields,
            content: message.content,
            repeat_interval: repeatInterval,
            repeat_at: repeatAt,
            repeat_at_timestamp: repeatAt,
            created_at: now,
        };

        return this.db
            .then((db: Db) => {
                return db.collection(COLLECTION_NAME).insertOne(document);
            })
            .then(() => {
                logger.info(
                    "Message persisted.",
                    {
                        node_id: "repeater",
                        correlation_id: message.properties.headers.correlation_id,
                        process_id: message.properties.headers.process_id,
                    });
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
        const now = Date.now();
        const query = {repeat_at : { $lte : now} };

        let docs: IPersistedMessage[] = [];

        return this.find(query)
            .then((documents: IPersistedMessage[]) => {
                docs = documents;

                // skip deleteDocuments when nothing found
                if (documents.length === 0) {
                    const fake: DeleteWriteOpResultObject = { result: {}, deletedCount: 0 };
                    return Promise.resolve(fake);
                }

                return this.deleteDocuments(query);
            })
            .then((deletion: DeleteWriteOpResultObject) => {
                if (deletion.deletedCount !== docs.length) {
                    logger.error("MongoDb deleted count not equal with retrieved count.", { node_id: "repeater" });
                }
                return docs;
            })
            .catch((err) => {
                logger.error("MongoDb findExpired error.", { node_id: "repeater", error: err });
                return [];
            });
    }

    /**
     * Created new mongodb connection
     */
    private createConnection(): void {
        this.db = MongoClient.connect(`mongodb://${this.settings.host}/${this.settings.db}`)
            .then((db: Db) => {
                logger.info("MongoDb connection opened.", { node_id: "repeater" });

                return db;
            })
            .catch((err: any) => {
                logger.error("MongoDb connection error.", { node_id: "repeater", error: err });

                return null;
            });
    }

    /**
     *
     * @param query
     * @return {Promise<IPersistedMessage[]>}
     */
    private find(query: any): Promise<IPersistedMessage[]> {
        return this.db
            .then((db: Db) => {
                return db.collection(COLLECTION_NAME).find(query).toArray();
            });
    }

    /**
     *
     * @param query
     * @return {Promise<DeleteWriteOpResultObject>}
     */
    private deleteDocuments(query: any): Promise<DeleteWriteOpResultObject> {
        return this.db
            .then((db: Db) => {
                return db.collection(COLLECTION_NAME).deleteMany(query);
            });
    }

}

export default MongoMessageStorage;
