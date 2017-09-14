import { IAmqpDrainSettings } from "./node/drain/AmqpDrain";
import { IAmqpFaucetSettings } from "./node/faucet/AmqpFaucet";
import { INodeConfig } from "./topology/Configurator";
import { INodeConfigSkeleton } from "./topology/Configurator";
import { IDrainConfig } from "./topology/Configurator";
import { IFaucetConfig } from "./topology/Configurator";
import { IWorkerConfig } from "./topology/Configurator";
import { ICounterSettings} from "./topology/counter/Counter";

class Defaults {

    public static getNodeConfigDefaults(topoName: string, node: INodeConfigSkeleton): INodeConfig {
        return {
            id: node.id,
            next: [],
            worker: Defaults.getDefaultWorkerConfig(),
            faucet: Defaults.getDefaultFaucetConfig(topoName, node),
            drain: Defaults.getDefaultDrainConfig(topoName, node),
            resequencer: false,
            debug: {
                port: 8007,
                host: node.id,
                url: `http://${node.id}:8007/status`,
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
     * @param {string} topoName
     * @param {INodeConfigSkeleton} node
     * @return {IFaucetConfig}
     */
    public static getDefaultFaucetConfig(topoName: string, node: INodeConfigSkeleton): IFaucetConfig {
        const type = "faucet.amqp";
        const settings: IAmqpFaucetSettings = {
            node_id: node.id,
            exchange: { name: `pipes.${topoName}.events`, type: "direct", options: {} },
            queue: { name: `pipes.${topoName}.${node.id}`, options: {} },
            prefetch: 10000,
            dead_letter_exchange: { name: "pipes.dead-letter", type: "direct", options: {} },
            routing_key: `${topoName}.${node.id}`,
        };

        return { type, settings };
    }

    /**
     *
     * @param {string} topoName
     * @param {INodeConfigSkeleton} node
     * @return {IDrainConfig}
     */
    public static getDefaultDrainConfig(topoName: string, node: INodeConfigSkeleton): IDrainConfig {
        const type = "drain.amqp";
        const settings: IAmqpDrainSettings = {
            node_id: node.id,
            counter_event: {
                queue: {
                    name: `pipes.${topoName}.counter`,
                    options: {},
                },
            },
            followers: node.next.map((nextNode: string) => {
                return {
                    node_id: nextNode,
                    exchange: {
                        name: `pipes.${topoName}.events`,
                        type: "direct",
                        options: {},
                    },
                    queue: {
                        name: `pipes.${topoName}.${nextNode}`,
                        options: {},
                    },
                    routing_key: `${topoName}.${nextNode}`,
                };
            }),
            resequencer: true,
        };

        return { type, settings };
    }

    /**
     *
     * @param {string} topoName
     * @return {ICounterSettings}
     */
    public static getCounterDefaultSettings(topoName: string): ICounterSettings {
        return {
            sub: {
                queue: {
                    name: `pipes.${topoName}.counter`,
                    prefetch: 1,
                    options: {},
                },
            },
            pub: {
                routing_key: "job_done",
                exchange: {
                    name: `pipes.${topoName}.events`,
                    type: "direct",
                    options: {},
                },
            },
        };
    }

}

export default Defaults;
