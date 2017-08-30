
const express = require('express');
const logger = require('./logger')(module);

const STATUS_NODE_PREPARED = 200;
const STATUS_NODE_NOT_PREPARED = 503; // Service Unavailable
const STATUS_NODE_ERROR = 500; // Internal Server Error

const ROUTE_STATUS = '/status';
const ROUTE_OPEN = '/open';

class Node {

  /**
   * Single node representation
   *
   * @param id
   * @param worker
   * @param faucet
   * @param drain
   * @param debugPort
   * @param isInitial
   */
  constructor(id, worker, faucet, drain, debugPort, isInitial = false) {
    this.id = id;
    this.worker = worker;
    this.faucet = faucet;
    this.drain = drain;
    this.debugPort = debugPort;
    this.nodeStatus = STATUS_NODE_NOT_PREPARED;
    this.isInitial = isInitial;
  }

  /**
   * Opens all nodes except the first one
   *
   * @return {Promise}
   */
  prepare() {
    if (this.isInitial) {
      this.nodeStatus = STATUS_NODE_PREPARED;
      return Promise.resolve(() => {});
    }

    const f = this._openNode();
    f.then(() => { this.nodeStatus = STATUS_NODE_PREPARED; });
    return f;
  }

  /**
   * Opens node for work
   *
   * @return {Promise}
   * @private
   */
  _openNode() {
    return this.faucet.open(
      msgIn => this.worker.processData(msgIn),
      msgOut => this.drain.open(msgOut)
    );
  }

  /**
   * Starts node's http server
   *  1. provides self-status
   *  2. accepts signal to start itself in case of first node in topology
   *
   * @private
   */
  startServer() {
    const app = express();

    // All nodes have "/status" route to indicate their readiness
    app.get(ROUTE_STATUS, (req, resp) => {
      resp.sendStatus(this.nodeStatus);
    });

    // First node has "/open" node to start consuming from source
    if (this.isInitial) {
      app.get(ROUTE_OPEN, (req, resp) => {
        this._openNode()
          .then((run) => {
            resp.sendStatus(200);
            run();
          })
          .catch(() => {
            resp.sendStatus(STATUS_NODE_ERROR);
          });
      });
    }

    const server = app.listen(this.debugPort, () => {
      const sa = server.address();
      logger.debug(`Node '${this.id}' provides "${ROUTE_STATUS}" on: ${sa.address}:${sa.port}`);
      if (this.isInitial) {
        logger.debug(`Node '${this.id}' provides "${ROUTE_OPEN}" on: ${sa.address}:${sa.port}`);
      }
    });

    return server;
  }

}

module.exports = exports = Node;
