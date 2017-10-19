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

export interface INodeLabel {
    id: string; // unique id combining node_id and node_name
    node_id: string; // uuid of the node
    node_name: string; // human readable name of the node
    topology_id: string;
}

export interface INodeConfigSkeleton {
    // mandatory fields
    id: string;
    next: string[]; // list of ids

    // optional fields - will be filled by defaults if not present
    label?: INodeLabel;
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
    label: INodeLabel;
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
        const isResequencer = nodeSkeleton.resequencer || defaults.resequencer;

        const faucetSettings = nodeSkeleton.faucet || defaults.faucet;
        const workerSettings = nodeSkeleton.worker || defaults.worker;
        const drainSettings = nodeSkeleton.drain || defaults.drain;
        drainSettings.settings.resequencer = isResequencer;

        // add label to all node parts
        faucetSettings.settings.node_label = nodeSkeleton.label || defaults.label;
        workerSettings.settings.node_label = nodeSkeleton.label || defaults.label;
        drainSettings.settings.node_label = nodeSkeleton.label || defaults.label;

        return {
            id: nodeSkeleton.id,
            label: defaults.label,
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
