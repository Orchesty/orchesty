const logger = require('../../src/logger')(module);
const AbstractWorker = require('../../src/abstract-worker');

class MixMessagesWorker extends AbstractWorker {

  constructor() {
    super();
    this.name = 'MixMessagesWorker';
  }

  /**
   * @param message
   * @return {Promise}
   */
  processData(message) {
    let delay = 0;
    const sequenceId = message.getSequenceId();

    if (sequenceId % 2 === 0) {
      delay = 200;
    }

    return new Promise((resolve) => {
      setTimeout(() => {
        const { data, settings } = message.open();
        const outData = `${this.name} output`;
        message.setContent(JSON.stringify({ data: outData, settings }));
        message.setJobResultOK();

        return resolve(message);
      }, delay);
    });
  }

}

module.exports = MixMessagesWorker;
