
process.env.NODE_ENV = 'test';

const should = require('chai').should();

const logger = require('./../../src/logger')(module);
const prepareNode = require('./prepare-node');
const MixWorker = require('./mix-messages-worker');
const { UppercaseWorker } = require('./../../src/worker/uppercase-worker');

/**
 * RUN this test separately via: $ node_modules/mocha/bin/_mocha test/integration/single-node.test.js
 */
describe('Node processes data and notifies counter', () => {
  it('should return upper-cased data on the output', () => new Promise((resolve) => {
    const worker = new UppercaseWorker();
    const testDataOutputQueue = 'tst_output_single';
    const testCounterOutputQueue = 'tst_counter_output_single';
    const node = prepareNode('single', worker, testDataOutputQueue, testCounterOutputQueue);

    node.then(([testChannel, faucetSettings, drainSettings]) => {
      const body = { settings: {}, data: 'content' };
      const data = new Buffer(JSON.stringify(body));
      const opts = { headers: { job_id: 'abc123', sequence_id: 1 } };

      // Check if data were properly processed and sent to output
      testChannel.consume(testDataOutputQueue, (msg) => {
        testChannel.ack(msg);
        const processed = JSON.parse(msg.content.toString());

        processed.should.deep.equal({ settings: {}, data: 'CONTENT' });
        msg.properties.headers.job_id.should.equal('abc123');

        // send stream event to finish job
        const counterInEx = drainSettings.stream_event.exchange.name;
        const counterInRK = drainSettings.stream_event.routing_key;
        const counterOutOpts = { headers: { job_id: 'abc123', node_id: 'final_node' } };
        const counterOutContent = { result: { code: 0, message: '' }, route: { following: 0, multiplier: 1 } };
        const counterOutBody = new Buffer(JSON.stringify(counterOutContent));
        testChannel.publish(counterInEx, counterInRK, counterOutBody, counterOutOpts);
      });

      // Check if counter sent job finished message
      testChannel.consume(testCounterOutputQueue, (jobDoneMsg) => {
        testChannel.ack(jobDoneMsg);
        const expected = { id: 'abc123', total: 2, ok: 2, nok: 0, messages: [] };
        jobDoneMsg.content.toString().should.deep.equal(JSON.stringify(expected));
        testChannel.close();
        return resolve(true);
      });

      testChannel.publish(faucetSettings.exchange.name, faucetSettings.routing_key, data, opts);
    }).catch(logger.error);
  }));
});
describe('Mixed messages test', () => {
  it(
    'should run process and return messages in order as they were processed - resequencer OFF',
    () => new Promise((resolve) => {
      const worker = new MixWorker();
      const nodeOutput = [];
      const testDataOutputQueue = 'tst_output_mixed';
      const testCounterOutputQueue = 'tst_counter_output_mixed';
      const node = prepareNode('mixed', worker, testDataOutputQueue, testCounterOutputQueue);

      node.then(([testChannel, faucetSettings]) => {
        // Check if data were properly processed and sent to output
        testChannel.consume(testDataOutputQueue, (msg) => {
          testChannel.ack(msg);
          nodeOutput.push(msg.properties.headers.sequence_id);
          if (nodeOutput.length === 5) {
            // expected without sorting (without resequencer)
            const expectedOutput = [1, 3, 5, 2, 4];
            expectedOutput.should.deep.equal(nodeOutput);
            testChannel.close();
            resolve();
          }
        });

        for (let i = 1; i <= 5; i += 1) {
          const data = new Buffer(JSON.stringify({ settings: {}, data: `content ${i}` }));
          const opts = { headers: { job_id: 'test-job', sequence_id: i } };
          testChannel.publish(faucetSettings.exchange.name, faucetSettings.routing_key, data, opts);
        }
      }).catch(logger.error);
    }));
  it(
    'should run process and return messages in same order as on the input - resequencer ON',
    () => new Promise((resolve) => {
      const worker = new MixWorker();
      const nodeOutput = [];
      const testDataOutputQueue = 'tst_output_ordered';
      const testCounterOutputQueue = 'tst_counter_output_ordered';
      const node = prepareNode('ordered', worker, testDataOutputQueue, testCounterOutputQueue, true);

      node.then(([testChannel, faucetSettings]) => {
        // Check if data were properly processed and sent to output
        testChannel.consume(testDataOutputQueue, (msg) => {
          testChannel.ack(msg);
          nodeOutput.push(msg.properties.headers.sequence_id);
          if (nodeOutput.length === 5) {
            // expected sorted
            const expectedOutput = [1, 2, 3, 4, 5];
            expectedOutput.should.deep.equal(nodeOutput);
            testChannel.close();
            resolve();
          }
        });

        for (let i = 1; i <= 5; i += 1) {
          const data = new Buffer(JSON.stringify({ settings: {}, data: `content ${i}` }));
          const opts = { headers: { job_id: 'test-job', sequence_id: i } };
          testChannel.publish(faucetSettings.exchange.name, faucetSettings.routing_key, data, opts);
        }
      }).catch(logger.error);
    }));
});
