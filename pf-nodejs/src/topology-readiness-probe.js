
const express = require('express');
const request = require('request');

const logger = require('./logger')(module);

const HTTP_PORT = 8007;

const ERROR_STATUS = 503;
const SUCCESS_STATUS = 200;
const WAIT_TIMEOUT = 10000;
const PROBE_PATH = '/topology-status';

/**
 * TODO - add tests
 */
class TopologyReadinessProbe {

  constructor(port) {
    this.port = port || process.env.HTTP_PORT || HTTP_PORT;
    this.nodes = [];
  }

  addNode(node) {
    this.nodes.push(node);
  }

  /**
   * Starts readines probe server
   */
  start(listenCallback) {
    const app = express();
    app.get(PROBE_PATH, (req, resp) => {
      this._checkTopology()
        .then((res) => {
          resp.status(res.status).send(res.message);
        })
        .catch((err) => {
          resp.status(ERROR_STATUS).send(`Error: ${err}`);
        });
    });
    const server = app.listen(this.port, () => {
      logger.debug(`Topology Readiness Probe listening info on: ${server.address().address}:${server.address().port}`);
      listenCallback();
    });

    return server;
  }

  /**
   * @return {Promise}
   * @private
   */
  _checkTopology() {
    return new Promise((resolve) => {
      let resolved = false;
      let ready = 0;
      let failed = 0;
      let total = 0;
      const failedUrls = [];

      this.nodes.forEach((node) => {
        request(node.debug.url, (err, response) => {
          total += 1;

          if (!err && response.statusCode && response.statusCode === SUCCESS_STATUS) {
            ready += 1;
          } else {
            failed += 1;
            failedUrls.push({ url: node.debug.url, err });
          }

          if (!resolved && this.nodes.length === total) {
            resolved = true;
            if (total === ready) {
              resolve({ status: SUCCESS_STATUS, message: `All ${ready} nodes are ready.` });
            } else {
              let msg = `Topology status: ${ready} nodes are ready, ${failed}: node checks failed.`;
              msg = `${msg} Failed: ${JSON.stringify(failedUrls)}`;
              resolve({ status: ERROR_STATUS, message: msg });
            }
          }
        });
      });

      setTimeout(() => {
        if (!resolved) {
          resolved = true;
          resolve({ status: ERROR_STATUS, message: 'Timeout reached.' });
        }
      }, WAIT_TIMEOUT);
    });
  }

}

module.exports = TopologyReadinessProbe;
