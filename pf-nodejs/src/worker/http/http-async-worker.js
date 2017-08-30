const assert = require('assert');
const bodyParser = require('body-parser');
const express = require('express');
const request = require('request');

const HttpWorker = require('./../../worker/http/abstract-http-worker');
const logger = require('./../../logger')(module);
const resultCodes = require('./../../result-codes');

class HttpAsyncWorker extends HttpWorker {

  constructor(settings) {
    assert(settings.response, 'HttpAsyncWorker response settings not specified.');
    assert(settings.response.host, 'HttpAsyncWorker response.host setting not specified.');
    assert(settings.response.path, 'HttpAsyncWorker response.path setting not specified.');
    assert(settings.response.port, 'HttpAsyncWorker response.port setting not specified.');
    assert(settings.response.method, 'HttpAsyncWorker response.method setting not specified');

    super(settings.request.method, settings.request.url);

    this.settings = settings;
    this.pending = {};

    const server = express();
    server.use(bodyParser.json());
    server.post(settings.response.path, (req, resp) => { this.handleResult(req, resp); });
    server.listen(settings.response.port);

    const serverUrl = `${settings.response.host}:${settings.response.port}${settings.response.path}`;
    logger.debug(`Local server listening for '${settings.response.method}' on: '${serverUrl}'`);
  }

  /**
   * TODO - add timeout limitation
   *
   * @param inMsg
   * @return {Promise}
   */
  processData(inMsg) {
    let reqParams = this.getHttpRequestParams(inMsg);
    reqParams = this.addReplyToHeaders(reqParams);

    return new Promise((resolve, reject) => {
      request(reqParams, (err, response) => {
        if (err) {
          inMsg.setJobResultFailed(resultCodes.HTTP_ERROR, err);
          return reject(inMsg);
        }

        if (!response.statusCode || response.statusCode !== 200) {
          inMsg.setJobResultFailed(resultCodes.HTTP_ERROR, `Http response with code ${response.statusCode} received`);
          return reject(inMsg);
        }

        this.pending[inMsg.getId()] = { msg: inMsg, resolve, reject };
        return true;
      });
    });
  }

  /**
   * @param reqParams
   * @return {*}
   */
  addReplyToHeaders(reqParams) {
    if (!reqParams.headers) {
      reqParams.headers = {};
    }

    const respConf = this.settings.response;
    reqParams.headers.replyToUrl = `${respConf.host}:${respConf.port}${respConf.path}`;
    reqParams.headers.replyToMethod = respConf.method;

    return reqParams;
  }

  /**
   * @param req
   * @param res
   * @return {*}
   */
  handleResult(req, res) {
    if (!req.headers || !req.headers.job_id ||
        !req.headers['content-type'] || req.headers['content-type'] !== 'application/json') {
      res.status(400);
      return res.send('Missing correct "job_id" and/or "Content-Type" headers.');
    }

    const jobId = req.headers.job_id;
    if (!this.pending[jobId]) {
      res.status(400);
      return res.send('Invalid "job_id" header');
    }

    const p = this.pending[jobId];
    const message = p.msg;
    delete this.pending[jobId];

    if (!req.body || !req.body.data || !req.body.settings) {
      // TODO - think about concrete failure status for this case instead of UNKNOWN_ERROR
      message.setJobResultFailed(resultCodes.UNKNOWN_ERROR);
      p.reject(message);
      res.status(200);
      return res.send('really ok? no data in body.');
    }

    message.setContent(JSON.stringify(req.body));
    message.setJobResultOK();
    p.resolve(message);
    res.status(200);
    return res.send('ok');
  }

}

module.exports = HttpAsyncWorker;
