class Configurator {

  constructor(config, defaults) {
    this.config = config;
    this.defaults = defaults;
    this.topologyName = config.topology_name || defaults.topologyName;
  }

  configCounter() {
    return this.defaults.counter;
  }

  * configNodes() {
    for (let i = 0; i < this.config.nodes.length; i += 1) {
      const isInitialNode = i === 0;
      const config = Configurator.normalizeConfig(this.config.nodes[i]);

      const node = Configurator.initNode(config, isInitialNode);
      node.faucet = config.faucet || this.configDefaultFaucet(config.id);
      node.drain = config.drain || this.configDefaultDrain(config);

      yield node;
    }
  }

  /**
   * Converts configuration to object if it is in array structure
   *
   * @param node
   * @return {*}
   */
  static normalizeConfig(node) {
    if (Array.isArray(node)) {
      const [id, worker, next] = node;

      return {
        id,
        worker,
        next: next ? next.map(nextNode => ({ node: nextNode })) : [],
      };
    }

    return node;
  }

  /**
   * @param nodeCfg
   * @param isInitialNode
   * @return {{id: *, isInitial: boolean, debug, worker: (*|module.exports.worker|{globals})}}
   */
  static initNode(nodeCfg, isInitialNode = false) {
    const node = { id: nodeCfg.id, isInitial: isInitialNode, debug: nodeCfg.debug, worker: nodeCfg.worker };

    if (!node.use_resequencer) {
      node.use_resequencer = false;
    }

    if (!node.debug) {
      node.debug = {};
    }

    if (!node.debug.port) {
      node.debug.port = 8007;
    }

    if (!node.debug.host) {
      node.debug.host = `http://${nodeCfg.id}`;
    }

    if (!node.debug.url) {
      node.debug.url = `${node.debug.host}:${node.debug.port}/status`;
    }

    return node;
  }

  /**
   * TODO: Tightly coupled with amqp
   *
   * @param nodeName
   * @return {AmqFaucet}
   */
  configDefaultFaucet(nodeName) {
    return {
      type: 'amq',
      config: {
        exchange: this.defaults.streamExchange,
        queue: { name: `pipes.${this.topologyName}.${nodeName}`, options: this.defaults.streamQueue.options },
        prefetch: this.defaults.streamQueue.prefetch,
        dead_letter_exchange: this.defaults.deadLetterExchange,
        routing_key: `${this.topologyName}.${nodeName}`,
      }
    };
  }

  /**
   *
   * @param fromNode
   * @param useResequencer
   * @return {AMQPDrain}
   */
  configDefaultDrain(fromNode) {
    return {
      type: 'amq',
      amqSettings: {
        node_id: fromNode.id,
        stream_event: this.defaults.streamEvent,
        following_pipes: fromNode.next.map(nextNode => (
          {
            exchange: this.defaults.streamExchange,
            routing_key: `${this.topologyName}.${nextNode.node}`,
          }
        )),
      }
    };
  }

}

module.exports = exports = Configurator;
