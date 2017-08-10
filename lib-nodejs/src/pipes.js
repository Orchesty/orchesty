const logger = require('./logger')(module);
const Node = require('./node');

// AMQ connection
const AmqpConnectionConfig = require('./pipes/amqp/connection-config');
const Connection = require('./pipes/amqp/connection');

const amqConnectionConfig = new AmqpConnectionConfig();
const amqConnection = new Connection(amqConnectionConfig);

// Configuration
const Configurator = require('./pipes/configurator');
const defaults = require('./pipes/amqp/defaults');

// Services
const Counter = require('./counter');
const TopologyProbe = require('./topology-readiness-probe');
const DockerCompose = require('./docker-compose-generator');

const container = require('./container')(amqConnection);

class Pipes {

  /**
   *
   * @param topology
   */
  constructor(topology) {
    this.topologyName = topology.topology_name;
    this.configurator = new Configurator(topology, defaults, amqConnection);
  }

  /**
   *
   * @private
   */
  * _createNodes() {
    for (const nodeCfg of this.configurator.configNodes()) {
      const { type: faucetType, config: faucetConfig } = nodeCfg.faucet;
      const { type: drainType, amqSettings, resequencer, config: drainConfig } = nodeCfg.drain;
      const { type: workerType, config: workerConfig } = nodeCfg.worker;

      const node = new Node(
        nodeCfg.id,
        container.workers[workerType](workerConfig),
        container.faucets[faucetType](faucetConfig),
        container.drains[drainType](amqSettings, resequencer, drainConfig),
        nodeCfg.debug.port,
        nodeCfg.isInitial,
        nodeCfg.resequencer ? nodeCfg.resequencer : null
      );

      yield node;
    }
  }

  /**
   * Find node config in list of nodes by it's id
   *
   * @param id
   * @return {*}
   * @private
   */
  _findNode(id) {
    for (const node of this._createNodes()) {
      if (node.id === id) return node;
    }

    throw new Error(`Node[id=${id}] not found.`);
  }

  /**
   * Run nodes
   *
   * @param nodes
   * @return {Promise.<TResult>}
   * @private
   */
  _startNodes(nodes) {
    const nodesPrepared = [];

    const ports = [];

    nodes.forEach((node) => {
      ports.push(node.startServer().address().port);
      nodesPrepared.push(node.prepare());
    });

    return Promise.all(nodesPrepared).then(
      (runs) => {
        runs.forEach((run) => {
          run();
        });
        logger.info(`Prepared ${nodesPrepared.length} nodes of topology "${this.topologyName}"`);

        return ports;
      },
      logger.error
    );
  }

  /**
   * Starts selected node
   *
   * @param nodeId
   * @return {Promise.<TResult>}
   */
  startNode(nodeId) {
    return this._startNodes([this._findNode(nodeId)]);
  }

  /**
   * Start all nodes
   *
   * @return {Promise.<TResult>}
   */
  startNodes() {
    return this._startNodes(Array.from(this._createNodes()));
  }

  /**
   * Starts topology counter
   */
  startCounter() {
    (new Counter(this.configurator.configCounter(), amqConnection)).listen();
  }

  /**
   * Starts topology probe
   *
   * @param port
   * @param listenCallback
   * @return {*}
   */
  startProbe(port, listenCallback) {
    const probe = new TopologyProbe(port);

    return probe.start(() => {
      for (const nodeCfg of this.configurator.configNodes()) {
        probe.addNode(nodeCfg);
      }

      if (listenCallback) listenCallback();
    });
  }

  generateDockerCompose(file) {
    DockerCompose.generate(this.configurator.configNodes(), amqConnectionConfig, file);
  }
}

module.exports = exports = topology => new Pipes(topology);
