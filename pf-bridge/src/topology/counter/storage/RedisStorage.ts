import * as redis from "redis";
import {RedisClient} from "redis";
import logger from "../../../logger/Logger";
import {ICounterProcessInfo} from "../CounterProcess";
import ICounterStorage from "./ICounterStorage";

export default class RedisStorage implements ICounterStorage {

    private client: RedisClient;

    constructor(
        host: string,
        port: number,
        password: string = "",
        db: number = 0,
    ) {
        this.client = redis.createClient({
            host,
            port,
            password,
            db,
        });
    }

    /**
     *
     * @param {string} topology
     * @param {string} processId
     * @return {Promise<boolean>}
     */
    public has(topology: string, processId: string): Promise<boolean> {
        return new Promise((resolve) => {
            this.client.hexists(topology, processId, (err, isThere) => {
                if (err) {
                    logger.error("Error calling has on counter process info in redis storage.", {error: err});
                    return resolve(false);
                }

                if (isThere) {
                    return resolve(true);
                }

                resolve(false);
            });
        });
    }

    /**
     *
     * @param {string} topology
     * @return {Promise<boolean>}
     */
    public hasSome(topology: string): Promise<boolean> {
        return new Promise((resolve) => {
            this.client.hlen(topology, (err, count) => {
                if (err) {
                    logger.error("Error calling hasSome on counter process info in redis storage.", {error: err});
                    return resolve(false);
                }

                if (count > 0) {
                    return resolve(true);
                }

                resolve(false);
            });
        });
    }

    /**
     *
     * @param {string} topology
     * @param {string} processId
     * @return {Promise<ICounterProcessInfo | null>}
     */
    public get(topology: string, processId: string): Promise<ICounterProcessInfo | null> {
        return new Promise((resolve) => {
            this.client.hget(topology, processId, (err, value) => {
                if (err) {
                    logger.error("Cannot get counter process info from redis storage.", {error: err});
                    return resolve(null);
                }

                try {
                    const process = JSON.parse(value);
                    resolve(process);
                } catch (err) {
                    logger.error("Cannot parse counter process info from redis storage.", {error: err});
                    return resolve(null);
                }
            });
        });
    }

    /**
     *
     * @param {string} topology
     * @param {ICounterProcessInfo} info
     * @return {Promise<boolean>}
     */
    public add(topology: string, info: ICounterProcessInfo): Promise<boolean> {
        return new Promise((resolve) => {
            const value = JSON.stringify(info);
            this.client.hset(topology, info.process_id, value, (err) => {
                if (err) {
                    logger.error("Cannot add counter process info to redis storage.", {error: err});
                    return resolve(false);
                }

                resolve(true);
            });
        });
    }

    /**
     *
     * @param {string} topology
     * @param {string} processId
     * @return {Promise<boolean>}
     */
    public remove(topology: string, processId: string): Promise<boolean> {
        return new Promise((resolve) => {
            this.client.hdel(topology, processId, (err) => {
                if (err) {
                    logger.error("Cannot de;ete counter process info from redis storage.", {error: err});
                    return resolve(false);
                }

                resolve(true);
            });
        });
    }
}
