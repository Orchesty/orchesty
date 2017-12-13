import {ICounterProcessInfo} from "../CounterProcess";
import ICounterStorage from "./ICounterStorage";

interface IProcessesMap {
    [key: string]: ICounterProcessInfo;
}

interface ITopologiesMap {
    [key: string]: IProcessesMap;
}

class InMemoryStorage implements ICounterStorage {

    private topologies: ITopologiesMap;

    constructor() {
        this.topologies = {};
    }

    /**
     *
     * @param {string} topology
     * @param {string} processId
     * @return {Promise<boolean>}
     */
    public async has(topology: string, processId: string): Promise<boolean> {
        if (this.topologies[topology]) {
            if (this.topologies[topology][processId]) {
                return Promise.resolve(true);
            }
        }

        return Promise.resolve(false);
    }

    /**
     *
     * @param {string} topology
     * @param {string} processId
     * @return {Promise<ICounterProcessInfo | null>}
     */
    public async get(topology: string, processId: string): Promise<ICounterProcessInfo | null> {
        const has = await this.has(topology, processId);
        if (has) {
            return Promise.resolve(this.topologies[topology][processId]);
        }

        return Promise.resolve(null);
    }

    /**
     *
     * @param {string} topology
     * @param {ICounterProcessInfo} info
     * @return {Promise<boolean>}
     */
    public async add(topology: string, info: ICounterProcessInfo): Promise<boolean> {
        if (!this.topologies[topology]) {
            this.topologies[topology] = {};
        }

        this.topologies[topology][info.process_id] = info;

        return Promise.resolve(true);
    }

    /**
     *
     * @param {string} topology
     * @param {string} processId
     * @return {Promise<boolean>}
     */
    public async remove(topology: string, processId: string): Promise<boolean> {
        const has = await this.has(topology, processId);
        if (has) {
            delete this.topologies[topology][processId];

            const hasSome = await this.hasSome(topology);
            if (!hasSome) {
                delete this.topologies[topology];
            }

            return Promise.resolve(true);
        }

        return Promise.resolve(false);
    }

    /**
     *
     * @param {string} topology
     * @return {Promise<boolean>}
     */
    public async hasSome(topology: string): Promise<boolean> {
        if (this.topologies[topology]) {
            if (Object.keys(this.topologies[topology]).length > 0) {
                return Promise.resolve(true);
            }
        }

        return Promise.resolve(false);
    }

}

export default InMemoryStorage;
