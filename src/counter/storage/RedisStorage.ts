import {ICounterProcessInfo} from "../CounterProcess";
import ICounterStorage from "./ICounterStorage";
import RedisPool from "node-redis-connection-pool/dist/src/RedisConnectionPool";

export interface IRedisStorageSettings {
    host: string;
    port: number;
    db: number;
    password?: string;
}

export default class RedisStorage implements ICounterStorage {

    private client: RedisPool;

    /**
     *
     * @param {IRedisStorageSettings} opts
     */
    constructor(opts: IRedisStorageSettings) {
        // Avoid redis client warning when trying to connect with empty password
        if (opts.password === "") {
            delete opts.password;
        }

        this.client = new RedisPool({
            name: '',
            poolOptions: {
                max: 10,
            },
            logger: null,
            redisOptions: opts,
        });
    }

    /**
     *
     * @param {string} hash
     * @return {boolean}
     */
    public async isProcessed(hash: string): Promise<boolean> {
        const result = await this.client.sendCommand('exists', [hash]);

        return !!result;
    }

    /**
     *
     * @param {string} hash
     */
    public async setProcessed(hash: string) {
        await this.client.sendCommand('set', [hash, '1', 'EX', 600]);
    }

    /**
     *
     * @param {string} topology
     * @param {string} processId
     * @return {Promise<boolean>}
     */
    public async has(topology: string, processId: string): Promise<boolean> {
        const result = await this.client.sendCommand('hexists', [topology, processId]);

        return !!result;
    }

    /**
     *
     * @param {string} topology
     * @return {Promise<boolean>}
     */
    public async hasSome(topology: string): Promise<boolean> {
        const result = await this.client.sendCommand('hlen', [topology]);

        return result > 0;
    }

    /**
     *
     * @param {string} topology
     * @param {string} processId
     * @return {Promise<ICounterProcessInfo | null>}
     */
    public async get(topology: string, processId: string): Promise<ICounterProcessInfo | null> {
        return await this.client.sendCommand('hget', [topology, processId]);
    }

    /**
     *
     * @param {string} topology
     * @param {ICounterProcessInfo} info
     * @return {Promise<boolean>}
     */
    public async add(topology: string, info: ICounterProcessInfo): Promise<boolean> {
        const value = JSON.stringify(info);
        await this.client.sendCommand('hset', [info.process_id, value]);

        return true;
    }

    /**
     *
     * @param {string} topology
     * @param {string} processId
     * @return {Promise<boolean>}
     */
    public async remove(topology: string, processId: string): Promise<boolean> {
        await this.client.sendCommand('hdel', [topology, processId]);

        return true;
    }

    /**
     * Finish all transactions and close connection to redis
     *
     * @return {Promise<void>}
     */
    public stop(): Promise<void> {
        return null
    }
}
