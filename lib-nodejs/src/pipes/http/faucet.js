/* eslint class-methods-use-this: ["error", { "exceptMethods": ["_handleResult"] }] */

const assert = require('assert');
const bodyParser = require('body-parser');
const express = require('express');

const AbstractFaucet = require('./../../abstract-faucet');
const logger = require('./../../logger')(module);
const Message = require('./../../job-message');

class HttpFaucet extends AbstractFaucet {

  /**
   * @param port
   */
  constructor(port) {
    super();
    this.port = port;
    this.path = '/';
  }

  /**
   * @param processData
   * @param drain
   * @return {Promise.<function()>}
   */
  open(processData, drain) {
    const app = express();
    app.use(bodyParser.json());
    app.post(this.path, (req, resp) => { this._handleResult(req, resp, processData, drain); });
    const server = app.listen(this.port, () => {
      logger.debug(`HttpFaucet ready. Listening on: ${server.address().address}:${server.address().port}${this.path}`);
    });

    return Promise.resolve(() => {});
  }

  /**
   * @param req
   * @param resp
   * @param processData
   * @param drain
   * @return {*|{line, column}}
   * @private
   */
  _handleResult(req, resp, processData, drain) {
    let body;
    try {
      assert(req.headers, 'Http request must contain headers.');
      assert(req.headers.job_id, 'Http request must contain "job_id" header.');
      assert(req.headers.sequence_id, 'Http request must contain "sequence_id" header.');
      assert(req.body, 'Http request must contain some content.');
      assert(req.body.data, 'Http request content must contain "data" field.');
      assert(req.body.settings, 'Http request content must contain "settings" field.');
      body = JSON.stringify(req.body);
    } catch (e) {
      return resp.status(500).end(e.message);
    }

    const inMsg = new Message(req.headers, body);

    return processData(inMsg)
      .then((outMsg) => {
        drain(outMsg);
        resp.sendStatus(200);
      })
      .catch((err) => {
        logger.error(`HttpFaucet processData error: ${err}`);
        resp.status(500).end(err);
      });
  }


}

exports.HttpFaucet = HttpFaucet;
exports.factory = () => config => new HttpFaucet(config.port);
