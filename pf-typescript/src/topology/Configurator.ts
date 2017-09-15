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
    name: string;
    nodes: INodeConfigSkeleton[];
    counter?: ICounterSettings;
}

export interface ITopologyConfig {
    name: string;
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
            nodes.push(Configurator.createNodeConfig(topologySkeleton.name, nodeSkeleton, i === 0));
            i++;
        });

        return {
            name: topologySkeleton.name,
            nodes,
            counter: topologySkeleton.counter || Defaults.getCounterDefaultSettings(topologySkeleton.name),
        };
    }

    /**
     *
     * @param {string} topoName
     * @param {INodeConfigSkeleton} nodeSkeleton
     * @param {boolean} isInitial
     * @return {INodeConfig}
     */
    private static createNodeConfig(
        topoName: string,
        nodeSkeleton: INodeConfigSkeleton,
        isInitial: boolean = false,
    ): INodeConfig {
        const defNode: INodeConfig = Defaults.getNodeConfigDefaults(topoName, nodeSkeleton);

        const faucetSettings = nodeSkeleton.faucet || defNode.faucet;
        faucetSettings.settings.node_id = nodeSkeleton.id;

        const workerSettings = nodeSkeleton.worker || defNode.worker;
        workerSettings.settings.node_id = nodeSkeleton.id;

        const isResequencer = nodeSkeleton.resequencer || defNode.resequencer;
        const drainSettings = nodeSkeleton.drain || defNode.drain;
        drainSettings.settings.resequencer = isResequencer;
        drainSettings.settings.node_id = nodeSkeleton.id;

        return {
            id: nodeSkeleton.id,
            next: nodeSkeleton.next,
            worker: workerSettings,
            faucet: faucetSettings,
            drain: drainSettings,
            resequencer: isResequencer,
            debug: nodeSkeleton.debug || defNode.debug,
            initial: isInitial,
        };
    }

}

export default Configurator;
