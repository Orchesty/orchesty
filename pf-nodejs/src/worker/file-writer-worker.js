const fs = require('fs');
const EOL = require('os').EOL;

const AbstractWorker = require('./../abstract-worker');
const resultCodes = require('./../result-codes');

class FileWriterWorker extends AbstractWorker {

  /**
   * @param file
   */
  constructor(file) {
    super();
    this.file = file;
  }

  /**
   *
   * @param message
   * @return {Promise}
   */
  processData(message) {
    return new Promise((resolve) => {
      const { data } = message.open();
      fs.appendFile(this.file, `${data}${EOL}`, 'utf8', (err) => {
        if (err) {
          message.setJobResultFailed(resultCodes.UNKNOWN_ERROR, err.message);
          return resolve(message);
        }

        message.setJobResultOK();
        return resolve(message);
      });
    });
  }

}

exports.FileWriterWorker = FileWriterWorker;
exports.factory = config => new FileWriterWorker(config.file);
