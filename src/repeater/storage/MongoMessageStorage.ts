import {Message} from "amqplib";
import {Db, DeleteWriteOpResultObject, MongoClient, MongoClientOptions} from "mongodb";
import logger from "../../logger/Logger";
import IMessageStorage from "./IMessageStorage";
import Headers from "../../message/Headers";

const RECONNECT_INTERVAL = 5000;

export interface IMongoMessageStorageSettings {
    dsn: string;
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
    public async save(message: Message, timeout: number): Promise<boolean> {
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

        const bodyHeaders = JSON.parse(message.content.toString()).headers;
        const ctx = {
            node_name: "repeater",
            correlation_id: bodyHeaders[Headers.CORRELATION_ID],
            process_id: bodyHeaders[Headers.PROCESS_ID],
        };

        try {
            const mongo: Db = await this.db;
            await mongo.collection(COLLECTION_NAME).insertOne(document);
            logger.debug("Message persisted.",ctx);
            return true;
        } catch (e) {
            const errCtx = {...ctx, error: e}
            logger.error("Cannot save document", errCtx);
            return false;
        }
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
                    logger.error("MongoDb deleted count not equal with retrieved count.", { node_name: "repeater" });
                }
                return docs;
            })
            .catch((err) => {
                logger.error("MongoDb findExpired error.", { node_name: "repeater", error: err });
                return [];
            });
    }

    /**
     * Created new mongodb connection
     */
    private createConnection(): void {
        const options: MongoClientOptions = {useNewUrlParser: true, useUnifiedTopology: true};

        this.db = MongoClient.connect(this.settings.dsn, options)
            .then(async (client: MongoClient) => {
                logger.info("MongoDb connection opened.", { node_name: "repeater" });

                await client.db().collection(COLLECTION_NAME).createIndex({'repeat_at': 1});

                return client.db();
            })
            .catch((err: any) => {
                logger.error("MongoDb connection error.", { node_name: "repeater", error: err });

                setTimeout(() => { this.createConnection(); }, RECONNECT_INTERVAL);

                return null;
            });
    }

    /**
     *
     * @param query
     * @return {Promise<IPersistedMessage[]>}
     */
    private async find(query: any): Promise<IPersistedMessage[]> {
        try {
            const mongo: Db = await this.db;

            return mongo.collection(COLLECTION_NAME).find(query).limit(500).toArray();
        } catch (e) {
            logger.error(
                `Error finding mongo document. Query: ${JSON.stringify(query)}`,
                { node_name: "repeater", error: e },
            );

            return [];
        }
    }

    /**
     *
     * @param query
     * @return {Promise<DeleteWriteOpResultObject>}
     */
    private async deleteDocuments(query: any): Promise<DeleteWriteOpResultObject> {
        try {
            const mongo: Db = await this.db;

            return mongo.collection(COLLECTION_NAME).deleteMany(query);
        } catch (e) {
            logger.error(
                "Cannot delete documents",
                {
                    node_name: "repeater",
                    data: JSON.stringify(query),
                    error: e,
                },
            );

            return {
                result: {ok: 0, n: 0},
                deletedCount: 0,
            };
        }
    }

}

export default MongoMessageStorage;
