const logger = require('../logger')(module);
const AbstractWorker = require('../abstract-worker');

class LoggerWorker extends AbstractWorker {

  constructor() {
    super();
    this.name = 'Example Logger AMQP Worker';
  }

  /**
   * @param message
   * @return {Promise}
   */
  processData(message) {
    return new Promise((resolve) => {
      logger.info(`Processing ${message.getId()} message with '${this.name}', data: ${message.getContent()}`);
      setTimeout(() => {
        const { settings: s } = message.open();
        const outData = 'Logger worker output.';
        message.setContent(JSON.stringify({ data: outData, settings: s }));
        message.setJobResultOK();

        return resolve(message);
      }, 1000);
    });
  }

}

module.exports = LoggerWorker;
