
const AbstractEventMessage = require('./abstract-event-message');

class NodeResultEventMessage extends AbstractEventMessage {

  /**
   *
   * @param jobId
   * @param nodeId
   * @param resultCode
   * @param resultMsg
   * @param following
   * @param multiplier
   */
  constructor(jobId, nodeId, resultCode = 1, resultMsg = '', following = 0, multiplier = 1) {
    super(jobId, nodeId);

    this.resultCode = resultCode;
    this.resultMsg = resultMsg;
    this.following = following;
    this.multiplier = multiplier;
  }

  /**
   * @return {string}
   */
  getContent() {
    return JSON.stringify({
      result: {
        code: this.resultCode,
        message: this.resultMsg,
      },
      route: {
        following: this.following,
        multiplier: this.multiplier,
      },
    });
  }

}

module.exports = NodeResultEventMessage;
