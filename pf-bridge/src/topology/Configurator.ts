import {amqpFaucetOptions, counterOptions, persistentMode, repeaterOptions} from "../config";
import { ICounterSettings } from "../counter/Counter";
import {IAmqpDrainSettings} from "../node/drain/AmqpDrain";
import {IAmqpFaucetSettings} from "../node/faucet/AmqpFaucet";

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
    topology_id: string;
    topology_name: string;
    nodes: INodeConfigSkeleton[];
    counter?: ICounterSettings;
}

export interface ITopologyConfig {
    id: string;
    topology_id: string;
    topology_name: string;
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
     * @param {string} topoId
     * @return {ICounterSettings}
     */
    public static getCounterDefaultSettings(isMulti: boolean, topoId: string): ICounterSettings {
        if (isMulti) {
            return {
                sub: {
                    queue: {
                        name: "pipes.multi-counter",
                        prefetch: counterOptions.prefetch,
                        options: {
                            durable: persistentMode,
                        },
                    },
                },
                pub: {
                    routing_key: "process_finished",
                    exchange: {
                        name: `pipes.events`,
                        type: "direct",
                        options: {},
                    },
                    queue: {
                        name: "pipes.results",
                        options: {
                            durable: persistentMode,
                        },
                    },
                },
            };
        }

        return {
            sub: {
                queue: {
                    name: `pipes.${topoId}.counter`,
                    prefetch: counterOptions.prefetch,
                    options: {
                        durable: persistentMode,
                    },
                },
            },
            pub: {
                routing_key: "process_finished",
                exchange: {
                    name: `pipes.${topoId}.events`,
                    type: "direct",
                    options: {},
                },
                queue: {
                    name: "pipes.results",
                    options: {
                        durable: persistentMode,
                    },
                },
            },
        };
    }

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
            const node = this.createNodeConfig(skeleton.topology_id, nodeSkeleton, i, isMulti);
            nodes.push(node);
            i++;
        });

        return {
            id: skeleton.id,
            topology_id: skeleton.topology_id,
            topology_name: skeleton.topology_name,
            nodes,
            counter: skeleton.counter || this.getCounterDefaultSettings(isMulti, skeleton.topology_id),
        };
    }

    /**
     *
     * @param {string} topoId
     * @param {INodeConfigSkeleton} nodeSkeleton
     * @param {number} nodePosition
     * @param {boolean} isMulti
     * @return {INodeConfig}
     */
    private static createNodeConfig(
        topoId: string,
        nodeSkeleton: INodeConfigSkeleton,
        nodePosition: number,
        isMulti: boolean = false,
    ): INodeConfig {
        const defaults: INodeConfig = this.getNodeConfigDefaults(topoId, nodeSkeleton, nodePosition, isMulti);

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

    /**
     *
     * @param {string} topoId
     * @param {INodeConfigSkeleton} node
     * @param {number} position
     * @param {boolean} isMulti
     * @return {INodeConfig}
     */
    private static getNodeConfigDefaults(
        topoId: string,
        node: INodeConfigSkeleton,
        position?: number,
        isMulti: boolean = false,
    ): INodeConfig {
        const port = position ? 8008 + position : 8008;

        return {
            id: node.id,
            label: {
                id: node.id,
                node_id: node.label ? node.label.node_id : node.id,
                node_name: node.label ? node.label.node_name : `${node.id}_unknown`,
                topology_id: topoId,
            },
            next: [],
            worker: this.getDefaultWorkerConfig(),
            faucet: this.getDefaultFaucetConfig(topoId, node),
            drain: this.getDefaultDrainConfig(topoId, node, isMulti),
            debug: {
                port,
                host: node.id,
                url: `http://${node.id}:${port}/status`,
            },
            initial: false,
        };
    }

    /**
     *
     * @return {IWorkerConfig}
     */
    private static getDefaultWorkerConfig(): IWorkerConfig {
        const type = "worker.null";
        const settings = {};

        return { type, settings };
    }

    /**
     *
     * @param {string} topoId
     * @param {INodeConfigSkeleton} node
     * @return {IFaucetConfig}
     */
    private static getDefaultFaucetConfig(topoId: string, node: INodeConfigSkeleton): IFaucetConfig {
        const type = "faucet.amqp";
        const settings: IAmqpFaucetSettings = {
            node_label: node.label,
            exchange: { name: `pipes.${topoId}.events`, type: "direct", options: {} },
            queue: {
                name: `pipes.${topoId}.${node.id}`,
                options: {
                    durable: persistentMode,
                },
            },
            prefetch: amqpFaucetOptions.prefetch,
            dead_letter_exchange: amqpFaucetOptions.dead_letter_exchange,
            routing_key: `${topoId}.${node.id}`,
        };

        return { type, settings };
    }

    /**
     *
     * @param {string} topoId
     * @param {INodeConfigSkeleton} node
     * @param {boolean} isMulti
     * @return {IDrainConfig}
     */
    private static getDefaultDrainConfig(
        topoId: string,
        node: INodeConfigSkeleton,
        isMulti: boolean = false,
    ): IDrainConfig {
        const type = "drain.amqp";
        const faucetConf = this.getDefaultFaucetConfig(topoId, node);
        const followers = node.next || [];
        const settings: IAmqpDrainSettings = {
            node_label: node.label,
            counter: {
                queue: {
                    name: isMulti ? "pipes.multi-counter" : `pipes.${topoId}.counter`,
                    options: {
                        durable: persistentMode,
                    },
                },
            },
            repeater: {
                queue: {
                    name: repeaterOptions.input.queue.name || `pipes.repeater`,
                    options: repeaterOptions.input.queue.options || {
                        durable: persistentMode,
                    },
                },
            },
            faucet: {
                queue: {
                    name: faucetConf.settings.queue.name,
                    options: faucetConf.settings.queue.options,
                },
            },
            followers: followers.map((nextNode: string) => {
                return {
                    node_id: nextNode,
                    exchange: {
                        name: `pipes.${topoId}.events`,
                        type: "direct",
                        options: {},
                    },
                    queue: {
                        name: `pipes.${topoId}.${nextNode}`,
                        options: {
                            durable: persistentMode,
                        },
                    },
                    routing_key: `${topoId}.${nextNode}`,
                };
            }),
        };

        return { type, settings };
    }

}

export default Configurator;
