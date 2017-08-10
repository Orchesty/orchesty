const assert = require('assert');
const fs = require('fs');
const EOL = require('os').EOL;

const AbstractDrain = require('./../../abstract-drain');
const logger = require('./../../logger')(module);
const ResultMessage = require('./../../node-result-event-message');

// TODO - almost identical with AMQPDrain, but cannot be ovverriden,
// Error: Process finished with exit code 139 (interrupted by signal 11: SIGSEGV)
class FsDrain extends AbstractDrain {

  constructor(settings, connection, resequencer, outputFile) {
    assert(settings.node_id, 'Invalid Drain settings: "node_id".');
    assert(settings.stream_event, 'Invalid Drain settings: "stream_event".');
    settings.following_pipes = [];

    super(settings, resequencer);
    this.outputFile = outputFile;
    this.nodeId = settings.node_id;
    this.connection = connection;
    this._prepareDrain();
  }

  /**
   *
   * @param message
   */
  open(message) {
    this.channel.then((ch) => {
      this.getMessageBuffer(message).forEach((bufMsg) => {
        this._publishOutputMessages(bufMsg, ch);
      });
    });
  }

  /**
   * Creates channel.
   */
  _prepareDrain() {
    const prepareFn = (channel) => {
      const streamEx = this.settings.stream_event.exchange;
      const eventEx = channel.assertExchange(streamEx.name, streamEx.type, streamEx.options);
      return Promise.all([eventEx]);
    };

    this.channel = this.connection.createChannelAndPrepare(prepareFn);

    this.channel.then((ch) => {
      ch.on('close', () => this._prepareDrain());
      logger.info(`FS drain [nodeId=${this.nodeId}] ready.`);
    });
  }

  /**
   * @param message
   * @param channel
   */
  _publishOutputMessages(message, channel) {
    this._publishStreamEvent(message, channel);
    this._writeToFile(message);
    logger.debug(`FsDrain published output message ${message.getId()}.`);
  }

  /**
   *
   * @param message
   * @param channel
   * @return void
   */
  _publishStreamEvent(message, channel) {
    const ex = this.settings.stream_event.exchange.name;
    const routKey = this.settings.stream_event.routing_key;

    const resMsg = new ResultMessage(
      message.getJobId(),
      this.nodeId,
      message.getJobResultCode(), // 0 OK, >0 NOK
      message.getJobResultMessage(),
      this.settings.following_pipes.length,
      1 // TODO - fill real multiplier value in case of "splitter" nodes
    );

    const opts = { headers: resMsg.getHeaders() };


    if (!channel.publish(ex, routKey, Buffer.from(resMsg.getContent()), opts)) {
      // @todo wait for drain event
    }
  }

  /**
   * Writes message to file
   *
   * @param message
   * @return void
   */
  _writeToFile(message) {
    const { data } = message.open();
    fs.appendFile(this.outputFile, `${data}${EOL}`, 'utf8', (err) => {
      if (err) {
        logger.error(`FsDrain, cannot write to file. ${err}`);
      }
    });
  }

}

exports.FsDrain = FsDrain;
exports.factory = connection => (amqSettings, resequencer, config) => new FsDrain(amqSettings, connection, resequencer, config.outputFile);
