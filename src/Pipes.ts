import {Container} from "hb-utils/dist/lib/Container";
import {IMetrics} from "metrics-sender/dist/lib/metrics/IMetrics";
import {mongoStorageOptions, probeOptions, repeaterOptions} from "./config";
import DIContainer from "./DIContainer";
import logger from "./logger/Logger";
import IDrain from "./node/drain/IDrain";
import IFaucet from "./node/faucet/IFaucet";
import Node from "./node/Node";
import IWorker from "./node/worker/IWorker";
import MongoMessageStorage from "./repeater/MongoMessageStorage";
import Repeater from "./repeater/Repeater";
import {default as Configurator, INodeConfig, ITopologyConfig, ITopologyConfigSkeleton} from "./topology/Configurator";
import Counter from "./topology/counter/Counter";
import Probe from "./topology/probe/Probe";
import Defaults from "./Defaults";

class Pipes {

    public nodes: Container;
    private dic: DIContainer;

    /**
     *
     * @param {ITopologyConfig | ITopologyConfigSkeleton} topology
     */
    constructor(private topology: ITopologyConfig | ITopologyConfigSkeleton) {
        this.nodes = new Container();
        this.dic = new DIContainer();
    }

    /**
     * Starts single node by its ID and opens it http probe server
     *
     * @param {string} nodeId
     * @return {Promise<void>}
     */
    public startNode(nodeId: string): Promise<Node> {
        const nodeConf = this.getNodeConfig(nodeId);
        const node: Node = this.createNode(nodeConf);

        return node.startServer(nodeConf.debug.port)
            .then(() => {
                return node.open();
            })
            .then(() => {
                logger.info(`Node started`, { node_id: nodeId });

                return node;
            });
    }

    /**
     *
     * @return {Promise<void>}
     */
    public startMultiBridge(): Promise<void> {
        const topo = this.getTopologyConfig(true);
        const proms: Node[] = [];

        for (const nodeCfg of topo.nodes) {
            this.startNode(nodeCfg.id)
                .then((node: Node) => {
                    proms.push(node);
                });
        }

        return Promise.all(proms)
            .then(() => {
                const multiProbe = this.dic.get("probe.multi");
                multiProbe.addTopology(topo);
                return;
            });
    }

    /**
     * Starts topology counter
     * @return {Promise<Counter>}
     */
    public async startCounter(): Promise<Counter> {
        const topo = this.getTopologyConfig(false);

        const counter = new Counter(
            topo.counter,
            this.dic.get("amqp.connection"),
            this.dic.get("counter.storage"),
            this.dic.get("topology.terminator")(false),
            this.dic.get("metrics")(topo.id, "counter"),
        );

        await counter.start();

        logger.info(`Counter for topology "${topo.id}" is running.`);

        return counter;
    }

    /**
     * Starts counter capable to manage multiple topologies
     *
     * @return {Promise<Counter>}
     */
    public async startMultiCounter(): Promise<Counter> {
        const topo = this.getTopologyConfig(true);

        const counter = new Counter(
            topo.counter,
            this.dic.get("amqp.connection"),
            this.dic.get("counter.storage"),
            this.dic.get("topology.terminator")(true),
            this.dic.get("metrics")(topo.id, "counter"),
        );

        await counter.start();

        logger.info(`MultiCounter is running.`);

        return counter;
    }

    /**
     * Starts topology probe
     */
    public startProbe(): Promise<void> {
        const topo = this.getTopologyConfig(false);
        const probe = new Probe(topo.id, probeOptions);

        for (const nodeCfg of topo.nodes) {
            probe.addNode(nodeCfg);
        }

        return probe.start()
            .then(() => {
                logger.info(`Probe of topology "${topo.id}" is running.`);
            });
    }

    /**
     * Creates and starts repeater service
     */
    public startRepeater(): void {
        const storage = new MongoMessageStorage(mongoStorageOptions);
        const repeater = new Repeater(repeaterOptions, this.dic.get("amqp.connection"), storage);

        repeater.run();
    }

    /**
     * Return the real full topology config
     *
     * @return {ITopologyConfig}
     */
    public getTopologyConfig(isMulti: boolean): ITopologyConfig {
        return Configurator.createConfigFromSkeleton(isMulti, this.topology);
    }

    /**
     *
     * @return {DIContainer}
     */
    public getDIContainer(): DIContainer {
        return this.dic;
    }

    /**
     *
     * @param {INodeConfig} nodeCfg
     * @return {Node}
     */
    private createNode(nodeCfg: INodeConfig): Node {
        const topo = this.getTopologyConfig(false);
        const id = nodeCfg.id;

        const faucet: IFaucet = this.dic.get(nodeCfg.faucet.type)(nodeCfg.faucet.settings);
        const drain: IDrain = this.dic.get(nodeCfg.drain.type)(nodeCfg.drain.settings);

        const splitterPrefix = DIContainer.WORKER_TYPE_SPLITTER;
        const worker: IWorker = (nodeCfg.worker.type.substring(0, splitterPrefix.length) === splitterPrefix) ?
            this.dic.get(nodeCfg.worker.type)(nodeCfg.worker.settings, drain) :
            this.dic.get(nodeCfg.worker.type)(nodeCfg.worker.settings);

        const metrics: IMetrics = this.dic.get("metrics")(topo.id, id);

        const node = new Node(id, worker, faucet, drain, metrics);

        this.nodes.set(nodeCfg.id, node);

        return node;
    }

    /**
     * Returns node config for particular node or throws error if node does not exist
     *
     * @param {string} nodeId
     * @return {INodeConfig}
     */
    private getNodeConfig(nodeId: string): INodeConfig {
        const topo = this.getTopologyConfig(false);
        for (const nodeCfg of topo.nodes) {
            if (nodeCfg.id === nodeId) {
                return nodeCfg;
            }
        }

        throw new Error(`Cannot get config for non-existing node "${nodeId}"`);
    }

}

export default Pipes;
