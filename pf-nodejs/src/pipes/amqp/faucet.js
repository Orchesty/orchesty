const logger = require('../../logger')(module);
const AbstractFaucet = require('../../abstract-faucet');
const Message = require('../../job-message');

class AmqFaucet extends AbstractFaucet {

  /**
   * @param settings
   * @param connection
   */
  constructor(settings, connection) {
    super();
    this.settings = settings;
    this.connection = connection;
  }

  /**
   * Creates channel and starts to consume messages.
   */
  open(processData, drain) {
    const prepareFn = (ch) => {
      const s = this.settings;
      return Promise.all([
        ch.assertQueue(s.queue.name, s.queue.options),
        ch.assertExchange(
          s.exchange.name,
          s.exchange.type,
          Object.assign(s.exchange.options, { 'x-dead-letter-exchange': s.dead_letter_exchange.name })),
        ch.assertExchange(
          s.dead_letter_exchange.name,
          s.dead_letter_exchange.type,
          s.dead_letter_exchange.options),
        ch.prefetch(s.prefetch),
      ]).then(([ok, ex]) => ch.bindQueue(ok.queue, ex.exchange, s.routing_key));
    };

    this.channel = this.connection.createChannelAndPrepare(prepareFn);

    return this.channel.then((ch) => {
      ch.on('close', () => this.open(processData, drain));
      logger.info(`AMQ faucet [queue=${this.settings.queue.name}] ready.`);

      return () => ch.consume(
          this.settings.queue.name,
          (msg) => { this._handleMessage(msg, ch, processData, drain); }
        );
    });
  }

  /**
   * @param amqMsg
   * @param ch
   * @param processData
   * @param drain
   */
  _handleMessage(amqMsg, ch, processData, drain) {
    const createMessage = new Promise((resolve, reject) => {
      try {
        const message = new Message(amqMsg.properties.headers, amqMsg.content.toString());
        resolve(message);
      } catch (e) {
        reject(e.message);
      }
    });

    createMessage.then((inMsg) => {
      processData(inMsg)
        .then((outMsg) => {
          ch.ack(amqMsg);
          drain(outMsg);
        })
        .catch((reason) => {
          logger.error(`Requeuing message. Reason: ${reason}`);
          ch.nack(reason); // requeue due to worker processing error
        });
    })
    .catch((reason) => {
      logger.error(`Dead-lettering message. Reason: ${reason}`);
      ch.nack(amqMsg, false, false); // dead-letter due to invalid message
    });
  }

}

exports.AmqFaucet = AmqFaucet;
exports.factory = connection => config => new AmqFaucet(config, connection);
