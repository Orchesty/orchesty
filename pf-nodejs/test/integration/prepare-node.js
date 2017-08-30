process.env.NODE_ENV = 'test';

const amqpConn = require('./test-amqp-connection');
const Counter = require('./../../src/counter');
const { AmqDrain } = require('./../../src/pipes/amqp/drain');
const { AmqFaucet } = require('./../../src/pipes/amqp/faucet');
const logger = require('./../../src/logger')(module);
const Resequencer = require('./../../src/resequencer');

let testChannel = null;

module.exports = (testId, worker, testDataOutputQueue, testCounterOutputQueue, useResequencer = false) =>
  new Promise((resolve, reject) => {
    const counterSettings = {
      sub: { queue: { name: `tst_co_q_${testId}`, prefetch: 1, options: {} } },
      pub: { exchange: { name: `tst_co_e_${testId}`, type: 'direct', options: {} }, routing_key: `tst_co_rk_${testId}` },
    };
    const drainSettings = {
      node_id: `test_node_${testId}`,
      stream_event: {
        exchange: { name: `tst_dr_se_e_${testId}`, type: 'direct', options: {} },
        routing_key: `tst_dr_se_rk_${testId}`
      },
      following_pipes: [
        { exchange: { name: `tst_dr_fp_e_${testId}`, type: 'direct', options: {} },
          routing_key: `tst_dr_fp_rk_${testId}`
        },
      ],
    };
    const faucetSettings = {
      exchange: { name: `tst_fa_e_${testId}`, type: 'direct', options: {} },
      queue: { name: `tst_fa_q_${testId}`, options: {} },
      dead_letter_exchange: { name: 'tst.dl', options: {} },
      prefetch: 5,
      routing_key: `tst_fa_rk_${testId}`,
    };

    const counter = new Counter(counterSettings, amqpConn);
    const faucet = new AmqFaucet(faucetSettings, amqpConn);
    let resequencer = null;
    if (useResequencer) {
      resequencer = new Resequencer();
    }
    const drain = new AmqDrain(drainSettings, amqpConn, resequencer);
    drain._prepareDrain().then(() => {
      counter.listen();
      amqpConn.connect()
        .then(conn => conn.createChannel())
        .then((ch) => {
          testChannel = ch;
          return Promise.all([
            ch.assertQueue(testDataOutputQueue),
            ch.assertQueue(testCounterOutputQueue)
          ]);
        })
        .then(() => {
          faucet.open(msgIn => worker.processData(msgIn), msgOut => drain.open(msgOut))
            .then((read) => {
              read();
              const ex = drainSettings.following_pipes[0].exchange.name;
              const rk = drainSettings.following_pipes[0].routing_key;

              const streamEventEx = drainSettings.stream_event.exchange.name;
              const streamEventRK = drainSettings.stream_event.routing_key;

              const counterOutEx = counterSettings.pub.exchange.name;
              const counterOutRK = counterSettings.pub.routing_key;

              Promise.all([
                testChannel.purgeQueue(testDataOutputQueue),
                testChannel.purgeQueue(testCounterOutputQueue),
                testChannel.purgeQueue(faucetSettings.queue.name),
                testChannel.bindQueue(testDataOutputQueue, ex, rk),
                testChannel.bindQueue(counterSettings.sub.queue.name, streamEventEx, streamEventRK),
                testChannel.bindQueue(testCounterOutputQueue, counterOutEx, counterOutRK),
              ])
              .then(() => {
                resolve([testChannel, faucetSettings, drainSettings]);
              });
            });
        })
        .catch((err) => {
          logger.error(err);
          reject(err);
        });
    });
  });
