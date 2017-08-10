const request = require('request');

const HttpWorker = require('./../../worker/http/abstract-http-worker');
const resultCodes = require('./../../result-codes');

class HttpSyncWorker extends HttpWorker {

  constructor(method, url, opts) {
    super(method, url);
    this.opts = opts;
  }

  /**
   * @param message
   * @return {Promise}
   */
  processData(message) {
    const reqParams = this.getHttpRequestParams(message);

    return new Promise((resolve, reject) => {

      Object.assign(reqParams, this.opts);

      // Make http request and wait for response
      request(reqParams, (err, response, body) => {
        if (err) {
          message.setJobResultFailed(resultCodes.HTTP_ERROR, err);
          return reject(message);
        }

        if (!response.statusCode || response.statusCode !== 200) {
          message.setJobResultFailed(resultCodes.HTTP_ERROR, `Http response with code ${response.statusCode} received`);
          return reject(message);
        }

        message.setContent(body);
        message.setJobResultOK();

        return resolve(message);
      });
    });
  }

}

module.exports = HttpSyncWorker;
