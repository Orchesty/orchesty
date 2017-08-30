/* eslint class-methods-use-this: ["error", { "exceptMethods": ["processData"] }] */

const AbstractWorker = require('./../abstract-worker');

class UppercaseWorker extends AbstractWorker {

  /**
   *
   * @param message
   * @return {Promise}
   */
  processData(message) {
    const { data, settings } = message.open();

    const outData = data.toUpperCase();

    message.setContent(JSON.stringify({ data: outData, settings }));
    message.setJobResultOK();

    return Promise.resolve(message);
  }

}

exports.UppercaseWorker = UppercaseWorker;
exports.factory = () => new UppercaseWorker();
