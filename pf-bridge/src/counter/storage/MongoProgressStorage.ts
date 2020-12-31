import {MongoClient} from "mongodb";
import {default as CounterMessage} from "../../message/CounterMessage";
import moment = require("moment");
import logger from "../../logger/Logger";

interface IPNodeProcess {
    processId: string;
    nodeId: string;
    nodeName: string;
    status: string;
    updated: Date;
}

interface IProcessMessage {
    _id?: string;
    correlationId: string;
    created: Date;
    duration: number;
    followers: number;
    startedAt: Date;
    status: string;
    topologyId: string;
    topologyName: string;
    finishedAt?: Date;
    nodes?: IPNodeProcess[];
}

export class MongoProgressStorage {

    private client?: Promise<MongoClient>;

    /**
     *
     * @param {boolean} enabled
     * @param {string} dsn
     * @param {string} collection
     * @param {number} expireAfter
     */
    public constructor(
        private readonly enabled: boolean,
        private readonly dsn: string,
        private readonly collection: string,
        private readonly expireAfter: number,
    ) {
        this.enabled = enabled;
        if (enabled) {
            this.client = new MongoClient(dsn, {useNewUrlParser: true, useUnifiedTopology: true}).connect();
            Promise.resolve(this.createIndexes(true));
            this.collection = collection;
            this.expireAfter = expireAfter;
        }
    }

    /**
     * @param {CounterMessage} cm
     * @param {number} end
     * @param {string} status
     */
    public upsertProgress(cm: CounterMessage, end?: number, status?: string): Promise<string> {
        const duration = moment(end ?? Date.now()).diff(moment(cm.getCreatedTime()), "millisecond");

        const node: IPNodeProcess = {
            processId: cm.getProcessId(),
            nodeId: cm.getNodeId(),
            nodeName: cm.getNodeLabel().node_name,
            status: cm.isOk() ? "OK" : "NOK",
            updated: new Date()
        };

        const document: IProcessMessage = {
            correlationId: cm.getCorrelationId(),
            created: new Date(),
            duration: duration,
            followers: cm.getFollowing() * cm.getMultiplier(),
            startedAt: new Date(cm.getCreatedTime()),
            status: status ?? "IP",
            topologyId: cm.getTopologyId(),
            topologyName: cm.getTopologyName(),
        };

        if (end) {
            document.finishedAt = new Date(end);
        }

        if (!this.enabled) {
            return new Promise((resolve) => {
                logger.debug('ProgressUpsert skipped.');
                resolve(JSON.stringify(document));
            });
        }

        const filter = {correlationId: document.correlationId, topologyId: document.topologyId};

        return new Promise((resolve, reject) => {

            this.createIndexes(false).then(
                (client) => {
                    const collection = client.db().collection(this.collection);
                    collection.updateOne(
                        filter,
                        {$set: document, $push: {nodes: node}},
                        {upsert: true},
                        (error) => {
                            if (error !== null) {
                                const m = `Failed to update document. Error: ${error}`;
                                logger.error(m);
                                return reject(m);
                            }
                        }
                    )

                    resolve(JSON.stringify(document));
                }
            );
        });
    }

    /**
     * @param {boolean} withCreateIndexes
     */
    private async createIndexes(withCreateIndexes: boolean): Promise<MongoClient> {
        return new Promise((resolve, reject) => {
            return this.client.then(
                (client) => {
                    if (!client.isConnected()) {
                        client.connect((err: Error) => {
                            if (err !== null) {
                                const m = `Failed to connect to MongoDB. Error: ${err}`;
                                logger.error(m);
                                return reject(m);
                            }
                        });
                    }

                    if (withCreateIndexes) {
                        const collection = client.db().collection(this.collection);
                        collection.createIndexes(
                            [
                                {key: {created: 1}, expireAfterSeconds: this.expireAfter},
                                {key: {correlationId: 1}},
                                {key: {topologyId: 1}},
                                {key: {correlationId: 1, topologyId: 1}}
                            ]
                        ).then(
                            () => {
                                logger.info("Indexes created successfully.");
                            },
                            (err) => {
                                const m = `Failed to create ensure index in MongoDB. Error: ${err}`;
                                logger.error(m);
                                return reject(m);
                            }
                        )
                    }

                    resolve(client);
                }
            )
        });
    }
}
