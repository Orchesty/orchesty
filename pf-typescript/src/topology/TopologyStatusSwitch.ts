import * as mongoose from "mongoose";
import {Connection, Model} from "mongoose";

export enum TOPOLOGY_STATUS {
    NEW = "new",
    STARTING = "starting",
    RUNNING = "running",
    STOPPED = "stopped",
}

export interface IMongoDBConnectionOptions {
    host: string;
    port: number;
    db: string;
    collection: string;
}

const Schema = mongoose.Schema;
export const topologySchema = new Schema({
    status: String,
});
const Topology = mongoose.model("Topology", topologySchema);

/**
 * Switch is responsible for changing topology status field in mongodb
 */
class TopologyStatusSwitch {

    private db: Connection;

    /**
     *
     * @param {IMongoDBConnectionOptions} connInfo
     */
    constructor(
        private connInfo: IMongoDBConnectionOptions,
    ) {
        this.db = mongoose.createConnection(connInfo.host, connInfo.db, connInfo.port);
    }

    /**
     *
     * @param {string} topoId
     * @param {TOPOLOGY_STATUS} newStatus
     * @return {Promise<any>}
     */
    public setStatus(topoId: string, newStatus: TOPOLOGY_STATUS) {
        return new Promise((resolve, reject) => {
            this.db.model(this.connInfo.collection, topologySchema, this.connInfo.collection)
                .findByIdAndUpdate(
                    topoId,
                    { $set: { status: newStatus } },
                    (err) => {
                        if (err) {
                            return reject(`Error switching topology status. Error: ${err}`);
                        }

                        return resolve(newStatus);
                    },
                );
        });
    }

}

export default TopologyStatusSwitch;
