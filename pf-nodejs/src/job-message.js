
const assert = require('assert');
const uuid = require('uuid/v1');

const resultCodes = require('./result-codes');

class JobMessage {

  /**
   *
   * @param headers {Object}
   * @param content {String}
   */
  constructor(headers, content) {
    assert(headers.job_id, 'Missing \'job_id\' message header.');
    assert(headers.sequence_id, 'Missing \'sequence_id\' message header.');

    this.headers = headers;
    this.content = content;
    this.sequenceId = Number.parseInt(headers.sequence_id, 10);
    this.msgId = `${headers.job_id}-${uuid()}`;
  }

  /**
   *
   * @return {string}
   */
  getId() {
    return this.msgId;
  }

  /**
   *
   * @return {string}
   */
  getJobId() {
    return this.headers.job_id;
  }

  /**
   *
   * @return {Number}
   */
  getSequenceId() {
    return this.sequenceId;
  }

  /**
   *
   * @param key
   * @param value
   */
  setHeader(key, value) {
    this.headers[key] = value;
  }

  /**
   *
   * @param key
   * @return {*}
   */
  getHeader(key) {
    if (this.headers[key] || this.headers[key] === 0) {
      return this.headers[key];
    }

    return null;
  }

  /**
   *
   * @return {*}
   */
  getHeaders() {
    return this.headers;
  }

  /**
   *
   * @param content {String}
   */
  setContent(content) {
    this.content = content;
  }

  /**
   *
   * @return {string}
   */
  getContent() {
    return this.content;
  }

  /**
   *
   * @return { data, settings }
   */
  open() {
    const parsed = JSON.parse(this.content);
    assert(parsed.data, `Opening message ${this.getId()}, but no data found inside.`);
    assert(parsed.settings, `Opening message ${this.getId()}, but no settings found inside.`);

    return parsed;
  }

  /**
   * @param message
   */
  setJobResultOK(message = '') {
    this.setHeader('result.code', resultCodes.SUCCESS);
    this.setHeader('result.message', message);
  }

  /**
   * @param errorCode
   * @param message
   */
  setJobResultFailed(errorCode, message = '') {
    this.setHeader('result.code', Number.parseInt(errorCode, 10));
    this.setHeader('result.message', message);
  }

  /**
   * Will return job status if is set, or 1 if not
   *
   * status === 0  => OK
   * status > 0  => NOK
   *
   * @return {int}
   */
  getJobResultCode() {
    if (this.getHeader('result.code') !== null) {
      return this.getHeader('result.code');
    }

    return 1;
  }

  /**
   * @return {string}
   */
  getJobResultMessage() {
    if (this.getHeader('result.message') !== null) {
      return this.getHeader('result.message');
    }

    return '';
  }

}

module.exports = JobMessage;
