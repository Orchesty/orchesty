const Resequencer = require('./resequencer');

class AbstractDrain {

  constructor(settings, resequencer = false) {
    if (this.constructor === AbstractDrain) {
      throw new TypeError('Abstract class "AbstractDrain" cannot be instantiated directly.');
    }

    if (this.open === undefined) {
      throw new TypeError('Classes extending the "AbstractDrain" class must implement "open" method');
    }

    this.settings = settings;
    this.resequencer = resequencer ? new Resequencer() : null;
  }

  getMessageBuffer(message) {
    return this.resequencer ? this.resequencer.getMessages(message) : [message];
  }
}

module.exports = AbstractDrain;
