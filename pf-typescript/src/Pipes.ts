import logger from "lib-nodejs/dist/src/logger/Logger";
import { default as Connection } from "lib-nodejs/dist/src/rabbitmq/Connection";
import { amqpConnectionOptions } from "./config";
import Container from "./Container";
import ComponentFactories from "./node/ComponentFactories";
import Node from "./node/Node";
import {default as Configurator, INodeConfig, ITopologyConfig, ITopologyConfigSkeleton} from "./topology/Configurator";
import Counter from "./topology/counter/Counter";
import DockerComposeGenerator from "./topology/DockerComposeGenerator";
import TopologyReadinessProbe from "./topology/TopologyReadinessProbe";

class Pipes {

    public nodes: Container;

    private amqpConn: Connection;
    private topology: ITopologyConfig;
    private components: ComponentFactories;

    constructor(topology: ITopologyConfig | ITopologyConfigSkeleton) {
        this.amqpConn = new Connection(amqpConnectionOptions);
        this.topology = Configurator.createConfigFromSkeleton(topology);
        this.nodes = new Container();
        this.components = new ComponentFactories(this.amqpConn);
    }

    /**
     * Starts single node by its ID
     * @param {string} nodeId
     * @return {Promise<void>}
     */
    public startNode(nodeId: string): Promise<void> {
        const node: Node = this.createNode(this.getNodeConfig(nodeId));

        return node.prepare()
            .then((run: () => void) => {
                run();
                return node.startServer();
            });
    }

    /**
     * Starts topology counter
     */
    public startCounter(): Promise<void> {
        const counter = new Counter(this.topology.counter, this.amqpConn);

        return counter.listen();
    }

    /**
     *
     * @param {number} port
     */
    public startProbe(port?: number): Promise<void> {
        const probe = new TopologyReadinessProbe(port);

        for (const nodeCfg of this.topology.nodes) {
            probe.addNode(nodeCfg);
        }

        return probe.start()
            .then(() => {
                logger.info("Topology probe is ready.");
            });
    }

    /**
     *
     * @param {string} file
     */
    public generateDockerCompose(file: string): void {
        DockerComposeGenerator.generate(this.topology.nodes, amqpConnectionOptions, file);
    }

    /**
     * Populates the list of nodes with instances
     */
    private createAllNodes(): void {
        for (const nodeCfg of this.topology.nodes) {
            this.createNode(nodeCfg);
        }
    }

    /**
     *
     * @param {INodeConfig} nodeCfg
     * @return {Node}
     */
    private createNode(nodeCfg: INodeConfig): Node {
        const node = new Node(
            nodeCfg.id,
            this.components.get(nodeCfg.worker.type)(nodeCfg.worker.settings),
            this.components.get(nodeCfg.faucet.type)(nodeCfg.faucet.settings),
            this.components.get(nodeCfg.drain.type)(nodeCfg.drain.settings),
            nodeCfg.debug.port,
            nodeCfg.initial,
        );

        this.nodes.set(nodeCfg.id, node);

        return node;
    }

    /**
     *
     * @param {string} nodeId
     * @return {INodeConfig}
     */
    private getNodeConfig(nodeId: string): INodeConfig {
        for (const nodeCfg of this.topology.nodes) {
            if (nodeCfg.id === nodeId) {
                return nodeCfg;
            }
        }

        throw new Error(`Cannot get config for non-existing node "${nodeId}"`);
    }

}

export default Pipes;
