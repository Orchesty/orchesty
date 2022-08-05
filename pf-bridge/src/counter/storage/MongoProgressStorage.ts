import {MongoClient} from "mongodb";
import {default as CounterMessage} from "../../message/CounterMessage";
import logger from "../../logger/Logger";
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
            this.createIndexes();
            this.collection = collection;
            this.expireAfter = expireAfter;
        }
    }

    /**
     * @param {CounterMessage} cm
     * @param {number} end
     * @param {string} status
     */
    public async upsertProgress(cm: CounterMessage, end?: number, status?: string): Promise<string> {
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
            logger.debug('ProgressUpsert skipped.');
            return JSON.stringify(document);
        }

        const filter = {correlationId: document.correlationId, topologyId: document.topologyId};
        const client = await this.reconnect();
        const collection = client.db().collection(this.collection);
        collection.updateOne(
            filter,
            {$set: document, $push: {nodes: node}},
            {upsert: true},
            (error) => {
                if (error !== null) {
                    logger.error(`Failed to update document. Error: ${error}`);
                }
            }
        )

        return JSON.stringify(document);
    }

    private async reconnect(): Promise<MongoClient> {
        const client = await this.client;
        if (!client.isConnected()) {
            try {
                await client.connect();
                logger.info('MongoDB successfully recconnected.')
            } catch (err) {
                logger.error(`Failed to connect to MongoDB. Error: ${err}`);
                throw err;
            }
        }
        return client;
    }

    private async createIndexes(): Promise<void> {
        const client = await this.reconnect();
        const collection = client.db().collection(this.collection);
        try {
            await collection.createIndexes(
                [
                    {key: {created: 1}, expireAfterSeconds: this.expireAfter},
                    {key: {correlationId: 1}},
                    {key: {topologyId: 1}},
                    {key: {correlationId: 1, topologyId: 1}}
                ]
            )
            logger.info('Indexes created successfully.');
        } catch (err) {
            logger.error(`Failed to create ensure index in MongoDB. Error: ${err}`);
            throw err;
        }
    }
}
