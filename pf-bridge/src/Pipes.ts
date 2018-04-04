import {Container} from "hb-utils/dist/lib/Container";
import {IMetrics} from "metrics-sender/dist/lib/metrics/IMetrics";
import {metricsOptions, mongoStorageOptions, probeOptions, repeaterOptions} from "./config";
import Counter from "./counter/Counter";
import Distributor from "./counter/distributor/Distributor";
import DIContainer from "./DIContainer";
import IStoppable from "./IStoppable";
import logger from "./logger/Logger";
import IDrain from "./node/drain/IDrain";
import IFaucet from "./node/faucet/IFaucet";
import Node from "./node/Node";
import IWorker from "./node/worker/IWorker";
import Probe from "./probe/Probe";
import Repeater from "./repeater/Repeater";
import MongoMessageStorage from "./repeater/storage/MongoMessageStorage";
import {default as Configurator, INodeConfig, ITopologyConfig, ITopologyConfigSkeleton} from "./topology/Configurator";
import INodeConfigProvider from "./topology/INodeConfigProvider";

class Pipes implements INodeConfigProvider {

    public nodes: Container;
    private dic: DIContainer;

    /**
     *
     * @param {ITopologyConfig | ITopologyConfigSkeleton} topology
     */
    constructor(private topology: ITopologyConfig | ITopologyConfigSkeleton) {
        this.nodes = new Container();
        this.dic = new DIContainer(this);
    }

    /**
     * Starts single node by its ID and opens it http probe server
     *
     * @param {string} nodeId
     * @param {boolean} isMulti
     * @return {Promise<void>}
     */
    public async startBridge(nodeId: string, isMulti: boolean = false): Promise<Node> {
        const nodeConf = this.getNodeConfig(nodeId, isMulti);
        const node: Node = this.createNode(nodeConf);

        await node.startServer(nodeConf.debug.port);
        await node.open();

        logger.info(`Bridge started`, { node_id: nodeId });

        return node;
    }

    /**
     *
     * @return {Promise<IStoppable[]>}
     */
    public async startMultiBridge(): Promise<IStoppable[]> {
        logger.debug("debug debug");
        const topo = this.getTopologyConfig(true);
        const proms: Node[] = [];

        for (const nodeCfg of topo.nodes) {
            const node = await this.startBridge(nodeCfg.id, true);
            proms.push(node);
        }

        const multiProbeConnector = this.dic.get("probe.multi");
        multiProbeConnector.addTopology(topo);

        return await Promise.all(proms);
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
            this.dic.get("counter.storage")(),
            new Distributor(),
            this.dic.get("topology.terminator")(false),
            this.dic.get("metrics")(topo.id, "counter", metricsOptions.counter_measurement),
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
        const topoId = "pipes.multi-counter";
        const counter = new Counter(
            Configurator.getCounterDefaultSettings(true, topoId),
            this.dic.get("amqp.connection"),
            this.dic.get("counter.storage")(),
            new Distributor(),
            this.dic.get("topology.terminator")(true),
            this.dic.get("metrics")(topoId, "multi-counter", metricsOptions.counter_measurement),
        );

        await counter.start();

        logger.info(`MultiCounter is running.`);

        return counter;
    }

    /**
     * DEPRECATED - use topology probe written in GoLang instead
     *
     * Starts topology probe
     */
    public async startProbe(): Promise<Probe> {
        const topo = this.getTopologyConfig(false);
        const probe = new Probe(topo.id, probeOptions);

        for (const nodeCfg of topo.nodes) {
            probe.addNode(nodeCfg);
        }

        await probe.start();

        logger.info(`Probe of topology "${topo.id}" is running.`);

        return probe;
    }

    /**
     * Creates and starts repeater service
     */
    public async startRepeater(): Promise<Repeater> {
        const storage = new MongoMessageStorage(mongoStorageOptions);
        const repeater = new Repeater(
            repeaterOptions,
            this.dic.get("amqp.connection"),
            storage,
        );

        await repeater.start();

        return repeater;
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
     * Returns node config for particular node or throws error if node does not exist
     *
     * @param {string} nodeId
     * @param {boolean} isMulti
     * @return {INodeConfig}
     */
    public getNodeConfig(nodeId: string, isMulti: boolean = false): INodeConfig {
        const topo = this.getTopologyConfig(isMulti);
        for (const nodeCfg of topo.nodes) {
            if (nodeCfg.id === nodeId) {
                return nodeCfg;
            }
        }

        throw new Error(`Cannot get config for non-existing node "${nodeId}"`);
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
            this.dic.get(nodeCfg.worker.type)(nodeCfg.worker.settings, drain, nodeCfg.drain.settings) :
            this.dic.get(nodeCfg.worker.type)(nodeCfg.worker.settings);

        const metrics: IMetrics = this.dic.get("metrics")(topo.id, id, metricsOptions.node_measurement);

        const node = new Node(id, worker, faucet, drain, metrics);

        this.nodes.set(nodeCfg.id, node);

        return node;
    }

}

export default Pipes;
