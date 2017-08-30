const assert = require('assert');
const AbstractDrain = require('../../abstract-drain');
const ResultMessage = require('../../node-result-event-message');
const logger = require('../../logger')(module);

class AmqDrain extends AbstractDrain {

  /**
   *
   * @param settings
   * @param connection
   * @param resequencer
   */
  constructor(settings, connection, resequencer = false) {
    assert(settings.node_id, 'Invalid Drain settings: "node_id".');
    assert(settings.stream_event, 'Invalid Drain settings: "stream_event".');
    assert(settings.following_pipes, 'Invalid Drain settings: "following_pipes"');

    super(settings, resequencer);
    this.nodeId = settings.node_id;
    this.connection = connection;
    this.channel = null;
  }

  /**
   *
   * @param message
   */
  open(message) {
    if (!this.channel) {
      this._prepareDrain().then(() => {
        this.open(message);
      });
      return;
    }

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
      const followersEx = this.settings.following_pipes.map(
        f => channel.assertExchange(f.exchange.name, f.exchange.type, f.exchange.options)
      );

      return Promise.all([eventEx, ...followersEx]);
    };

    this.channel = this.connection.createChannelAndPrepare(prepareFn);

    return this.channel.then((ch) => {
      ch.on('close', () => this._prepareDrain());
      logger.info(`AMQ drain [nodeId=${this.nodeId}] ready.`);
    });
  }

  /**
   * @param message
   * @param channel
   */
  _publishOutputMessages(message, channel) {
    this._publishStreamEvent(message, channel);
    this._publishFollowing(message, channel);
    logger.debug(`Drain published message ${message.getId()}.`);
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
   *
   * @param message
   * @param channel
   * @return void
   */
  _publishFollowing(message, channel) {
    const content = Buffer.from(message.getContent());
    const options = { headers: message.getHeaders(), messageId: message.getId() };

    this.settings.following_pipes.forEach((follower) => {
      if (!channel.publish(follower.exchange.name, follower.routing_key, content, options)) {
        // @todo wait for drain event
      }
    });
  }

}

exports.AmqDrain = AmqDrain;
exports.factory = connection => (amqSettings, resequencer = null) => new AmqDrain(amqSettings, connection, resequencer);
