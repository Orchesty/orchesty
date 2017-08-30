const AbstractWorker = require('./../../abstract-worker');

class AbstractHttpWorker extends AbstractWorker {

  constructor(method, url) {
    super();
    this.method = method;
    this.url = url;
  }

  /**
   * @param inMsg
   * @return {object}
   */
  getHttpRequestParams(inMsg) {
    const reqParams = {
      method: this.method.toUpperCase(),
      url: this.url,
      json: true,
      gzip: true,
      body: inMsg.getContent(),
      headers: {
        messageId: inMsg.getId()
      }
    };

    return reqParams;
  }

}

module.exports = AbstractHttpWorker;
