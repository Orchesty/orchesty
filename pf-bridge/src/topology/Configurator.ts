import { default as Defaults } from "../Defaults";
import { ICounterSettings } from "./counter/Counter";

export interface IWorkerConfig {
    type: string;
    settings: any;
}

export interface IFaucetConfig {
    type: string;
    settings: any;
}

export interface IDrainConfig {
    type: string;
    settings: any;
}

export interface INodeConfigSkeleton {
    // mandatory fields
    id: string;
    next: string[];
    // optional fields - will be filled by defaults if not present
    worker?: IWorkerConfig;
    faucet?: IFaucetConfig;
    drain?: IDrainConfig;
    resequencer?: boolean;
    debug?: {
        port: number,
        host: string,
        url: string,
    };
}

export interface INodeConfig {
    id: string;
    next: string[];
    worker: IWorkerConfig;
    faucet: IFaucetConfig;
    drain: IDrainConfig;
    resequencer: boolean;
    debug: {
        port: number,
        host: string,
        url: string,
    };
    initial: boolean;
}

export interface ITopologyConfigSkeleton {
    id: string;
    nodes: INodeConfigSkeleton[];
    counter?: ICounterSettings;
}

export interface ITopologyConfig {
    id: string;
    nodes: INodeConfig[];
    counter: ICounterSettings;
}

/**
 * Class used for creating proper configuration json with all necessary fields
 */
class Configurator {

    /**
     *
     * @param {ITopologyConfigSkeleton} topologySkeleton
     * @return {ITopologyConfig}
     */
    public static createConfigFromSkeleton(
        topologySkeleton: ITopologyConfigSkeleton,
    ): ITopologyConfig {
        const nodes: INodeConfig[] = [];

        let i = 0;
        topologySkeleton.nodes.forEach((nodeSkeleton: INodeConfigSkeleton) => {
            nodes.push(Configurator.createNodeConfig(topologySkeleton.id, nodeSkeleton, i === 0));
            i++;
        });

        return {
            id: topologySkeleton.id,
            nodes,
            counter: topologySkeleton.counter || Defaults.getCounterDefaultSettings(topologySkeleton.id),
        };
    }

    /**
     *
     * @param {string} topoId
     * @param {INodeConfigSkeleton} nodeSkeleton
     * @param {boolean} isInitial
     * @return {INodeConfig}
     */
    private static createNodeConfig(
        topoId: string,
        nodeSkeleton: INodeConfigSkeleton,
        isInitial: boolean = false,
    ): INodeConfig {
        const defaults: INodeConfig = Defaults.getNodeConfigDefaults(topoId, nodeSkeleton);

        const faucetSettings = nodeSkeleton.faucet || defaults.faucet;
        faucetSettings.settings.node_id = nodeSkeleton.id;

        const workerSettings = nodeSkeleton.worker || defaults.worker;
        workerSettings.settings.node_id = nodeSkeleton.id;

        const isResequencer = nodeSkeleton.resequencer || defaults.resequencer;
        const drainSettings = nodeSkeleton.drain || defaults.drain;
        drainSettings.settings.resequencer = isResequencer;
        drainSettings.settings.node_id = nodeSkeleton.id;

        return {
            id: nodeSkeleton.id,
            next: nodeSkeleton.next,
            worker: workerSettings,
            faucet: faucetSettings,
            drain: drainSettings,
            resequencer: isResequencer,
            debug: nodeSkeleton.debug || defaults.debug,
            initial: isInitial,
        };
    }

}

export default Configurator;
