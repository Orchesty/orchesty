const { factory: pipes_sync } = require('./worker/http/pipes-sync-worker');
const { factory: appender } = require('./worker/appender-worker');
const { factory: uppercase } = require('./worker/uppercase-worker');
const { factory: file_writer } = require('./worker/file-writer-worker');

const { factory: amqFaucetFactory } = require('./pipes/amqp/faucet');
const { factory: httpFaucetFactory } = require('./pipes/http/faucet');
const { factory: cmdFaucetFactory } = require('./pipes/cmd/faucet');

const { factory: amqDrainFactory } = require('./pipes/amqp/drain');
const { factory: fsDrainFactory } = require('./pipes/fs/drain');

class Container {
  constructor(amqConnection) {
    this.amqConnection = amqConnection;
  }

  init() {
    return {
      faucets: {
        amq: amqFaucetFactory(this.amqConnection),
        http: httpFaucetFactory(),
        cmd: cmdFaucetFactory
      },

      drains: {
        amq: amqDrainFactory(this.amqConnection),
        fs: fsDrainFactory(this.amqConnection)
      },

      workers: {
        pipes_sync,
        appender,
        uppercase,
        file_writer
      }
    };
  }
}



module.exports = exports = amqConnection => (new Container(amqConnection)).init();
