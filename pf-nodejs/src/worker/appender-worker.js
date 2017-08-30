const AbstractWorker = require('./../abstract-worker');

class AppenderWorker extends AbstractWorker {

  /**
   * @param settings
   */
  constructor(suffix) {
    super();
    this.suffix = suffix;
  }

  /**
   *
   * @param message
   * @return {Promise}
   */
  processData(message) {
    const { data, settings } = message.open();

    const outData = `${data}${this.suffix}`;

    message.setContent(JSON.stringify({ data: outData, settings }));
    message.setJobResultOK();

    return Promise.resolve(message);
  }

}

exports.AppenderWorker = AppenderWorker;
exports.factory = settings => new AppenderWorker(settings.suffix);
