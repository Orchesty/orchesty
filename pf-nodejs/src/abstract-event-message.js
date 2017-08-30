/* eslint class-methods-use-this: ["error", { "exceptMethods": ["getContent"] }] */

class AbstractEventMessage {

  /**
   *
   * @param jobId
   * @param nodeId
   */
  constructor(jobId, nodeId) {
    if (this.constructor === AbstractEventMessage) {
      throw new TypeError('Abstract class "AbstractEventMessage" cannot be instantiated directly.');
    }

    this.jobId = jobId;
    this.nodeId = nodeId;
  }

  /**
   * @return {{job_id: *, node_id: *}}
   */
  getHeaders() {
    return {
      job_id: this.jobId,
      node_id: this.nodeId,
    };
  }

  /**
   * @return {string}
   */
  getContent() {
    return '';
  }

}

module.exports = AbstractEventMessage;
