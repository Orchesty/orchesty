
const assert = require('assert');
const logger = require('./logger')(module);

const resultCodes = require('./result-codes');

const ID_DELIMITER = '.';

class Counter {

  /**
   *
   * @param settings
   * @param connection
   */
  constructor(settings, connection) {
    if (!settings.sub) {
      throw new TypeError('Missing counter subscribe settings.');
    }
    if (!settings.pub) {
      throw new TypeError('Missing counter publish settings.');
    }

    // Init empty jobs in-memory statistics structure
    this.jobs = {};
    this.settings = settings;
    this.connection = connection;
    this._openSub();
    this._openPub();
  }

  /**
   * Listen to the event stream and keep info about job partial results
   * On job end, send process end message.
   */
  listen() {
    const q = this.settings.sub.queue;

    this.subChannel
      .then((ch) => {
        logger.info('Counter is waiting for messages.');
        ch.consume(q.name, (msg) => {
          ch.ack(msg);
          this._handleMessage(msg);
        }, q.options);
      });
  }

  /**
   * Creates subscription channel
   */
  _openSub() {
    const prepareFn = (ch) => {
      const s = this.settings;
      ch.prefetch(s.sub.queue.prefetch);
      return ch.assertQueue(s.sub.queue.name, s.sub.queue.options);
    };

    this.subChannel = this.connection.createChannelAndPrepare(prepareFn);

    this.subChannel.then((ch) => {
      ch.on('close', () => {
        logger.error('Counter subscribe channel closed. Trying to open new channel.');
        this._openSub();
      });
    });
  }

  /**
   * Creates publish channel
   */
  _openPub() {
    const prepareFn = (ch) => {
      const pubExSett = this.settings.pub.exchange;
      return ch.assertExchange(pubExSett.name, pubExSett.type, pubExSett.options);
    };

    this.pubChannel = this.connection.createChannelAndPrepare(prepareFn);

    this.pubChannel.then((ch) => {
      ch.on('close', () => {
        logger.error('Counter publish channel closed. Trying to open new channel.');
        this._openPub();
      });
    });
  }

  /**
   * Handles incoming message
   *
   * @param msg
   */
  _handleMessage(msg) {
    let headers = null;
    let content = null;
    try {
      // validate headers
      assert(msg.properties, 'Missing message properites.');
      assert(msg.properties.headers, 'Missing message headers.');
      headers = msg.properties.headers;
      assert(headers.job_id, 'Missing "job_id" header field.');
      assert(headers.node_id, 'Missing "node_id" header field.');
      // validate content
      content = JSON.parse(msg.content.toString());
      assert(content.result, 'Missing "result" in message body.');
      assert.equal(true, Object.prototype.hasOwnProperty.call(content.result, 'code'),
        'Missing "result.code" in message body.');
      assert.equal(true, Object.prototype.hasOwnProperty.call(content.result, 'message'),
        'Missing "result.message" in message body.');
      assert.equal(true, Object.prototype.hasOwnProperty.call(content.route, 'following'),
        'Missing "route.following" in message body.');
      assert.equal(true, Object.prototype.hasOwnProperty.call(content.route, 'multiplier'),
        'Missing "route.multiplier" in message body.');
    } catch (e) {
      logger.error('Invalid event message. Error: ', e.message);
      logger.debug('Invalid event message. Message properties: ', msg.properties);
      logger.debug('Invalid event message. Message content: ', msg.content.toString());

      return false;
    }

    const jobId = Counter._getTopJobId(headers.job_id);
    const node = headers.node_id;
    const resultCode = Number.parseInt(content.result.code, 10);
    const following = Number.parseInt(content.route.following, 10);
    const multiplier = Number.parseInt(content.route.multiplier, 10);

    let messageObj = null;
    if (content.result.message !== '') {
      messageObj = { resultCode, node, message: content.result.message };
    }

    return this._handleJob(jobId, resultCode, following, multiplier, messageObj);
  }

  /**
   * @param jobId
   * @param status
   * @param following
   * @param multiplier
   * @param error
   * @return {*}
   */
  _handleJob(jobId, status, following, multiplier, error) {
    let job = this.jobs[jobId] ? this.jobs[jobId] : null;
    if (job) {
      job = Counter._updateJob(job, status, following, multiplier, error);
    } else {
      job = Counter._createJob(jobId);
      job = Counter._updateJob(job, status, following, multiplier, error);
    }

    if (Counter._isJobFinished(job)) {
      this._onJobFinished(this.jobs[jobId]);
      delete this.jobs[jobId];
    } else {
      // save job
      this.jobs[jobId] = job;
    }

    return job;
  }

  /**
   * Publish message informing that job is completed
   *
   * @param job
   */
  _onJobFinished(job) {
    this.pubChannel.then((ch) => {
      const e = this.settings.pub.exchange;
      const rKey = this.settings.pub.routing_key;
      ch.publish(e.name, rKey, Buffer.from(JSON.stringify(job)), {});
    });
  }

  /**
   * @param id
   * @return {*}
   */
  static _getTopJobId(id) {
    const stringId = `${id}`;
    const parts = stringId.split(ID_DELIMITER, 1);

    return parts[0];
  }

  /**
   * Creates new process object
   *
   * @param id
   * @return {object}
   */
  static _createJob(id) {
    return {
      id,
      total: 1,
      ok: 0,
      nok: 0,
      messages: [],
    };
  }

  /**
   *
   * @param job
   * @param result
   * @param following
   * @param multiplier
   * @param messageObj
   * @return {*}
   */
  static _updateJob(job, result, following = 0, multiplier = 1, messageObj = null) {
    if (result === resultCodes.SUCCESS) {
      job.ok += 1;
    } else {
      job.nok += 1;
    }

    job.total += multiplier * following;

    if (messageObj !== null) {
      job.messages.push(messageObj);
    }

    return job;
  }

  /**
   * Returns true if process is completely finished
   *
   * @param job
   * @return {boolean}
   */
  static _isJobFinished(job) {
    if (job.nok + job.ok === job.total) {
      return true;
    }
    return false;
  }

}

module.exports = Counter;
