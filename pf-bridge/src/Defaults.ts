import { repeaterOptions } from "./config";
import { IAmqpDrainSettings } from "./node/drain/AmqpDrain";
import { IAmqpFaucetSettings } from "./node/faucet/AmqpFaucet";
import { INodeConfig } from "./topology/Configurator";
import { INodeConfigSkeleton } from "./topology/Configurator";
import { IDrainConfig } from "./topology/Configurator";
import { IFaucetConfig } from "./topology/Configurator";
import { IWorkerConfig } from "./topology/Configurator";
import { ICounterSettings} from "./topology/counter/Counter";

class Defaults {

    /**
     *
     * @param {string} topoId
     * @param {INodeConfigSkeleton} node
     * @param {number} position
     * @param {boolean} isMulti
     * @return {INodeConfig}
     */
    public static getNodeConfigDefaults(
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
            worker: Defaults.getDefaultWorkerConfig(),
            faucet: Defaults.getDefaultFaucetConfig(topoId, node),
            drain: Defaults.getDefaultDrainConfig(topoId, node, isMulti),
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
    public static getDefaultWorkerConfig(): IWorkerConfig {
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
    public static getDefaultFaucetConfig(topoId: string, node: INodeConfigSkeleton): IFaucetConfig {
        const type = "faucet.amqp";
        const settings: IAmqpFaucetSettings = {
            node_label: node.label,
            exchange: { name: `pipes.${topoId}.events`, type: "direct", options: {} },
            queue: { name: `pipes.${topoId}.${node.id}`, options: {} },
            prefetch: 10000,
            dead_letter_exchange: { name: "pipes.dead-letter", type: "direct", options: {} },
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
    public static getDefaultDrainConfig(
        topoId: string,
        node: INodeConfigSkeleton,
        isMulti: boolean = false,
    ): IDrainConfig {
        const type = "drain.amqp";
        const faucetConf = Defaults.getDefaultFaucetConfig(topoId, node);
        const settings: IAmqpDrainSettings = {
            node_label: node.label,
            counter: {
                queue: {
                    name: isMulti ? "pipes.multi-counter" : `pipes.${topoId}.counter`,
                    options: {},
                },
            },
            repeater: {
                queue: {
                    name: repeaterOptions.input.queue.name || `pipes.repeater`,
                    options: repeaterOptions.input.queue.options || {},
                },
            },
            faucet: {
                queue: {
                    name: faucetConf.settings.queue.name,
                    options: faucetConf.settings.queue.options,
                },
            },
            followers: node.next.map((nextNode: string) => {
                return {
                    node_id: nextNode,
                    exchange: {
                        name: `pipes.${topoId}.events`,
                        type: "direct",
                        options: {},
                    },
                    queue: {
                        name: `pipes.${topoId}.${nextNode}`,
                        options: {},
                    },
                    routing_key: `${topoId}.${nextNode}`,
                };
            }),
        };

        return { type, settings };
    }

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
                        prefetch: 1,
                        options: {},
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
                        options: {},
                    },
                },
            };
        }

        return {
            sub: {
                queue: {
                    name: `pipes.${topoId}.counter`,
                    prefetch: 1,
                    options: {},
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
                    options: {},
                },
            },
        };
    }

}

export default Defaults;
