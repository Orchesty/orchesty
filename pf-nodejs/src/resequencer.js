
const moniker = require('moniker');
const logger = require('./logger')(module);

/**
 * Resequencer is responsible for outputting messages ordered by their sequence_id
 */
class Resequencer {

  /**
   * @param bufferTtl {Number} 86400000ms = 24h
   * TODO - implement sequence/storage size monitoring
   */
  constructor(bufferTtl = 86400000) {
    this.buffer = {};
    this.bufferTtl = bufferTtl;
    this.name = moniker.choose();
  }

  /**
   * Pass actual processed message.
   * This method will return the array of messages that continue
   * @param msg
   * @return {*}
   */
  getMessages(msg) {
    const buf = this._getBuffer(msg.getJobId());

    if (msg.getSequenceId() < buf.waitingFor) {
      let warn = `Resequencing already processed sequenceId ${msg.getSequenceId()} of job ${msg.getJobId()}`;
      warn += 'This is possible message duplicate and will be ignored.';
      logger.warn(warn);
      return [];
    }

    buf.messages[msg.getSequenceId()] = msg;

    if (msg.getSequenceId() > buf.waitingFor) {
      return [];
    }

    return this._getCompletedMessages(buf, msg);
  }

  /**
   * Retuns existing or creates new buffer
   *
   * @param jobId {string}
   * @private
   */
  _getBuffer(jobId) {
    if (!this.buffer[jobId]) {
      this._createBuffer(jobId);
    }

    return this.buffer[jobId];
  }

  _createBuffer(jobId) {
    this.buffer[jobId] = {
      messages: {},
      waitingFor: 1,
      timeout: setTimeout(() => { delete this.buffer[jobId]; }, this.bufferTtl),
    };
  }

  _getCompletedMessages(buffer, msg) {
    const out = [];
    while (true) {
      const desired = buffer.messages[buffer.waitingFor];
      if (!desired) {
        break;
      }

      out.push(desired);
      delete buffer.messages[buffer.waitingFor];
      buffer.waitingFor += 1;
      clearTimeout(buffer.timeout);
      buffer.timeout = setTimeout(() => { delete this.buffer[msg.getJobId()]; }, this.bufferTtl);
    }

    return out;
  }

}

module.exports = Resequencer;
