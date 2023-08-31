import {amqpFaucetOptions, counterOptions, persistentMessages, persistentQueues, repeaterOptions} from "../config";
import { ICounterSettings } from "../counter/Counter";
import {IAmqpDrainSettings} from "../node/drain/AmqpDrain";
import {IAmqpFaucetSettings} from "../node/faucet/AmqpFaucet";

export interface INodeComponentConfig {
    type: string;
    settings: any;
}

export interface IWorkerConfig extends INodeComponentConfig {}
export interface IFaucetConfig extends INodeComponentConfig {}
export interface IDrainConfig extends INodeComponentConfig {}

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
                            durable: persistentQueues,
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
                            durable: persistentQueues,
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
                        durable: persistentQueues,
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
                        durable: persistentQueues,
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
        // TODO - handle defaults more systematically (eg. merging provided config with defaults etc.)
        const defaults: INodeConfig = this.getNodeConfigDefaults(topoId, nodeSkeleton, nodePosition, isMulti);

        const faucetCfg = this.isValidComponentConfig(nodeSkeleton.faucet) ? nodeSkeleton.faucet : defaults.faucet;
        const workerCfg = this.isValidComponentConfig(nodeSkeleton.worker) ? nodeSkeleton.worker : defaults.worker;
        const drainCfg = this.isValidComponentConfig(nodeSkeleton.drain) ? nodeSkeleton.drain : defaults.drain;

        // add label to all node components
        faucetCfg.settings.node_label = defaults.label;
        workerCfg.settings.node_label = defaults.label;
        drainCfg.settings.node_label = defaults.label;

        return {
            id: nodeSkeleton.id,
            label: defaults.label,
            next: nodeSkeleton.next,
            worker: workerCfg,
            faucet: faucetCfg,
            drain: drainCfg,
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
        const prefetch = node.faucet && node.faucet.settings && node.faucet.settings.prefetch ?
            Number.parseInt(node.faucet.settings.prefetch, 10) :
            amqpFaucetOptions.prefetch;

        const settings: IAmqpFaucetSettings = {
            node_label: node.label,
            exchange: { name: `pipes.${topoId}.events`, type: "direct", options: {} },
            queue: {
                name: `pipes.${topoId}.${node.id}`,
                options: {
                    durable: persistentQueues,
                },
            },
            dead_letter_exchange: amqpFaucetOptions.dead_letter_exchange,
            routing_key: `${topoId}.${node.id}`,
            prefetch,
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
            persistent: persistentMessages,
            counter: {
                queue: {
                    name: isMulti ? "pipes.multi-counter" : `pipes.${topoId}.counter`,
                    options: {
                        durable: persistentQueues,
                    },
                },
            },
            repeater: {
                queue: {
                    name: repeaterOptions.input.queue.name || `pipes.repeater`,
                    options: repeaterOptions.input.queue.options || {
                        durable: persistentQueues,
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
                            durable: persistentQueues,
                        },
                    },
                    routing_key: `${topoId}.${nextNode}`,
                };
            }),
        };

        return { type, settings };
    }

    /**
     * Checks if valid node component (faucet, drain, worker) config structure was provided
     * @param cfg
     */
    private static isValidComponentConfig(cfg: any): boolean {
        return cfg && cfg.type && cfg.type.length > 0 && cfg.settings;
    }

}

export default Configurator;
