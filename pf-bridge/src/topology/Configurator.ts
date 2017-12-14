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
     * @param {boolean} isMulti
     * @param {ITopologyConfigSkeleton} skeleton
     * @return {ITopologyConfig}
     */
    public static createConfigFromSkeleton(isMulti: boolean, skeleton: ITopologyConfigSkeleton): ITopologyConfig {
        const nodes: INodeConfig[] = [];

        let i = 0;
        skeleton.nodes.forEach((nodeSkeleton: INodeConfigSkeleton) => {
            const node = Configurator.createNodeConfig(skeleton.id, nodeSkeleton, i);
            nodes.push(node);
            i++;
        });

        return {
            id: skeleton.id,
            nodes,
            counter: skeleton.counter || Defaults.getCounterDefaultSettings(isMulti, skeleton.id),
        };
    }

    /**
     *
     * @param {string} topoId
     * @param {INodeConfigSkeleton} nodeSkeleton
     * @param {number} nodePosition
     * @return {INodeConfig}
     */
    private static createNodeConfig(
        topoId: string,
        nodeSkeleton: INodeConfigSkeleton,
        nodePosition: number,
    ): INodeConfig {
        const defaults: INodeConfig = Defaults.getNodeConfigDefaults(topoId, nodeSkeleton, nodePosition);

        const faucetSettings = nodeSkeleton.faucet || defaults.faucet;
        const workerSettings = nodeSkeleton.worker || defaults.worker;
        const drainSettings = nodeSkeleton.drain || defaults.drain;

        // add label to all node parts
        faucetSettings.settings.node_label = defaults.label;
        workerSettings.settings.node_label = defaults.label;
        drainSettings.settings.node_label = defaults.label;

        return {
            id: nodeSkeleton.id,
            label: defaults.label,
            next: nodeSkeleton.next,
            worker: workerSettings,
            faucet: faucetSettings,
            drain: drainSettings,
            debug: nodeSkeleton.debug || defaults.debug,
            initial: nodePosition === 0,
        };
    }

}

export default Configurator;
