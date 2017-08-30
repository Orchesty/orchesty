/* eslint no-use-before-define: [1, 'nofunc'] */

const amqp = require('amqplib');
// const eol = require('os').EOL;
const logger = require('../../logger')(module);

const WAIT_MS = 2000;
const WAIT_MAX_MS = 300000; // 5 * 60 * 1000 = 5 min
// const WAIT_LIMIT = 24; // 12 * 5 min = 2 h

class AMQPConnection {

  /**
   * @param connConfig
   */
  constructor(connConfig) {
    this.connConfig = connConfig;
    this.connection = this._createConnection();
  }

  /**
   * Returns promise with current amqp connection
   *
   * @return {Promise}
   */
  connect() {
    return this.connection;
  }

  /**
   * Returns promise of channel with applied functions on the channel
   * @param prepareFn
   * @return {Promise}
   */
  createChannelAndPrepare(prepareFn) {
    return new Promise((resolve) => {
      let tryCount = 1;

      const tryAgain = (reason) => {
        const wait = Math.min(WAIT_MS * tryCount, WAIT_MAX_MS); // wait max 5 min

        logger.error(`Channel creation failed. Retry after ${wait} ms. Reason: ${reason}`);

        setTimeout(tryConnect, wait);
        tryCount += 1;
      };

      const tryConnect = () => this.connection
        .then(connection => connection.createChannel())
        .then((ch) => {
          prepareFn(ch)
            .then(() => { resolve(ch); });
        })
        .catch(tryAgain);

      tryConnect();
    });
  }

  /**
   * Creates new connection to RabbitMQ promise
   */
  _createConnection() {
    return new Promise((resolve/* , reject*/) => {
      let tryCount = 1;

      const tryAgain = (error) => {
        /* try forever
        if (tryCount >= (WAIT_MAX_MS / WAIT_MS) + WAIT_LIMIT) { // 5 min + 2 h
          reject(`Maximum (${tryCount}) reconnection attemps reached.`);
        }*/

        const wait = Math.min(WAIT_MS * tryCount, WAIT_MAX_MS); // wait max 5 min

        logger.error(`RabbitMQ connection failure. Retry after ${wait} ms. Reason: ${error.message}`);

        setTimeout(tryConnect, wait);
        tryCount += 1;
      };

      const tryConnect = () => amqp.connect(this.connConfig.getUrl(), { heartbeat: this.connConfig.getHeartbeat() })
            .then((connection) => {
              connection.on('close', (error) => {
                logger.warn(error.message);
                this.connection = this._createConnection();
              });
              logger.debug('Connected to RabbitMQ.');
              resolve(connection);
            })
            .catch(tryAgain);

      tryConnect();
    });
  }
}

module.exports = exports = AMQPConnection;
