import {MongoClient} from "mongodb";
import {default as CounterMessage} from "../../message/CounterMessage";
import moment = require("moment");

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
            this.collection = collection;
            this.expireAfter = expireAfter;
        }
    }

    /**
     * @param {CounterMessage} cm
     * @param end
     * @param status
     */
    public upsertProgress(cm: CounterMessage, end?: number, status?: string): Promise<string> {
        if (!this.enabled) {
            return new Promise(() => {
            });
        }

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

        const filter = {correlationId: document.correlationId, topologyId: document.topologyId};

        return new Promise((resolve, reject) => {

            this.client.then(
                (client) => {
                    if (!client.isConnected()) {
                        client.connect((err: Error) => {
                            if (err !== null) {
                                return reject(`Failed to connect to MongoDB. Error: ${err}`);
                            }
                        });
                    }

                    const collection = client.db().collection(this.collection);

                    collection.createIndex({created: 1}, {expireAfterSeconds: this.expireAfter}).then(
                        () => {
                            collection.updateOne(
                                filter,
                                {$set: document, $addToSet: {nodes: node}},
                                {upsert: true},
                                (error) => {
                                    if (error !== null) {
                                        return reject(`Failed to update document. Error: ${error}`);
                                    }
                                }
                            )
                        },
                        (err) => {
                            return reject(`Failed to create ensure index in MongoDB. Error: ${err}`);
                        }
                    );

                    resolve(JSON.stringify(document));
                },
                (err) => {
                    if (err !== null) {
                        return reject(`Failed to connect to MongoDB. Error: ${err}`);
                    }
                }
            )
        });
    }
}
