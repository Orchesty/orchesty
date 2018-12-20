import {default as CounterMessage} from "../../message/CounterMessage";
import logger from "../../logger/Logger";

export interface ISyncObject {
    msg: CounterMessage;
    resolve: any;
    reject: any;
}

interface IProcessesSyncMap {
    [key: string]: ISyncObject[];
}

interface ITopologiesSyncMap {
    [key: string]: IProcessesSyncMap;
}

/**
 * Distributor splits messages by their processId and returns them according to FIFO principle
 */
export default class Distributor {

    private queue: ITopologiesSyncMap;

    constructor() {
        this.queue = {};

        const self = this;
        setInterval(() => {
            Object.keys(self.queue).forEach((topology) => {
                logger.info(`Topology "${topology}" has ${self.queue[topology].length} opened processes`);
            });
        }, 1000 * 30);
    }

    public has(topo: string, process: string): boolean {
        if (!this.queue[topo]) {
            return false;
        }

        if (!this.queue[topo][process]) {
            return false;
        }

        if (this.queue[topo][process].length === 0) {
            return false;
        }

        return true;
    }

    /**
     *
     * @param {string} topo
     * @param {string} process
     * @return {ISyncObject[]}
     */
    public all(topo: string, process: string): ISyncObject[] {
        if (!this.queue[topo]) {
            this.queue[topo] = {};
        }

        if (!this.queue[topo][process]) {
            this.queue[topo][process] = [];
        }

        return this.queue[topo][process];
    }

    /**
     *
     * @param {string} topo
     * @param {string} process
     * @return {ISyncObject | undefined}
     */
    public first(topo: string, process: string): ISyncObject | undefined {
        return this.all(topo, process)[0];
    }

    /**
     *
     * @param {string} topo
     * @param {string} process
     * @return {number}
     */
    public length(topo: string, process: string): number {
        return this.all(topo, process).length;
    }

    /**
     *
     * @param {string} topo
     * @param {string} process
     * @param {ISyncObject} item
     */
    public add(topo: string, process: string, item: ISyncObject): void {
        this.all(topo, process).push(item);
    }

    /**
     *
     * @param {string} topo
     * @param {string} process
     * @return {ISyncObject | undefined}
     */
    public shift(topo: string, process: string): ISyncObject | undefined {
        const item =  this.all(topo, process).shift();
        this.deleteSoftly(topo, process);

        return item;
    }

    /**
     *
     * @param {string} topo
     * @param {string} process
     */
    public deleteSoftly(topo: string, process: string): void {
        if (this.length(topo, process) === 0) {
            delete this.queue[topo][process];
        }
    }

}
