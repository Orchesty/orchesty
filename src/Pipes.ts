import {Container} from "hb-utils/dist/lib/Container";
import {IMetrics} from "metrics-sender/dist/lib/metrics/IMetrics";
import * as os from "os";
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
import Probe from "./topology/Probe";

class Pipes {

    public nodes: Container;

    private topology: ITopologyConfig;
    private dic: DIContainer;

    constructor(topology: ITopologyConfig | ITopologyConfigSkeleton) {
        this.nodes = new Container();
        this.dic = new DIContainer();

        this.topology = Configurator.createConfigFromSkeleton(topology);
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
     * Starts topology counter
     */
    public startCounter(): Promise<void> {
        const counter = new Counter(
            this.topology.counter,
            this.dic.get("amqp.connection"),
            this.dic.get("metrics")(this.topology.id, `${os.hostname()}_counter`),
        );

        return counter.listen()
            .then(() => {
                logger.info(`Counter for topology "${this.getTopologyConfig().id}" is running.`);
            });
    }

    /**
     * Starts topology probe
     */
    public startProbe(): Promise<void> {
        const probe = new Probe(this.topology.id, probeOptions);

        for (const nodeCfg of this.topology.nodes) {
            probe.addNode(nodeCfg);
        }

        return probe.start()
            .then(() => {
                logger.info(`Probe of topology "${this.getTopologyConfig().id}" is running.`);
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
    public getTopologyConfig(): ITopologyConfig {
        return this.topology;
    }

    /**
     *
     * @param {INodeConfig} nodeCfg
     * @return {Node}
     */
    private createNode(nodeCfg: INodeConfig): Node {
        const id = nodeCfg.id;

        const faucet: IFaucet = this.dic.get(nodeCfg.faucet.type)(nodeCfg.faucet.settings);
        const drain: IDrain = this.dic.get(nodeCfg.drain.type)(nodeCfg.drain.settings);

        const splitterPrefix = DIContainer.WORKER_TYPE_SPLITTER;
        const worker: IWorker = (nodeCfg.worker.type.substring(0, splitterPrefix.length) === splitterPrefix) ?
            this.dic.get(nodeCfg.worker.type)(nodeCfg.worker.settings, drain) :
            this.dic.get(nodeCfg.worker.type)(nodeCfg.worker.settings);

        const metrics: IMetrics = this.dic.get("metrics")(this.topology.id, `${os.hostname()}_${id}`);

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
        for (const nodeCfg of this.topology.nodes) {
            if (nodeCfg.id === nodeId) {
                return nodeCfg;
            }
        }

        throw new Error(`Cannot get config for non-existing node "${nodeId}"`);
    }

}

export default Pipes;
