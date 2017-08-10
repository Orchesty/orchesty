
class AbstractWorker {

  constructor() {
    if (this.constructor === AbstractWorker) {
      throw new TypeError('Abstract class "AbstractWorker" cannot be instantiated directly.');
    }

    if (this.processData === undefined) {
      throw new TypeError('Classes extending the "AbstractWorker" abstract class must implement "process" method');
    }
  }
}

module.exports = AbstractWorker;
