const chai = require('chai');
const chaiFiles = require('chai-files');
const fs = require('fs');
const os = require('os');
const request = require('request');

chai.use(chaiFiles);
const should = chai.should();
const chaiFile = chaiFiles.file;

const amqpConn = require('./test-amqp-connection');
const defaults = require('./../../src/pipes/amqp/defaults');
const AppenderWorker = require('./../../src/worker/appender-worker');
const { HttpFaucet } = require('./../../src/pipes/http/faucet');
const FsDrain = require('./../../src/pipes/fs/drain');
const Resequencer = require('./../../src/resequencer');
const UppercaseWorker = require('./../../src/worker/uppercase-worker');
const pipesFactory = require('./../../src/pipes');

function resetFile(filePath) {
  const fd = fs.openSync(filePath, 'w');
  fs.writeSync(fd, new Buffer(''));
  fs.closeSync(fd);
}

function generateDebug(port) {
  return {
    port,
    url: `http://localhost:${port}/status`
  };
}

function prepareTopology(topologyName, portBase, outputFile, resequencer = false) {
  return new Promise((resolve) => {
    resetFile(outputFile);
    chaiFile(outputFile).should.be.empty;

    const firstFaucet = { type: 'http', config: { port: portBase } };// new HttpFaucet(portBase);
    const upper = { type: 'uppercase' };
    const appender = { type: 'appender', config: { suffix: '!' } };
    /*const fileWriter = new FsDrain(
      { outputFile, node_id: 'node_4', stream_event: defaults.streamEvent },
      amqpConn,
      new Resequencer()
    );*/
    const fileWriter = { type: 'fs', amqSettings: { node_id: 'node_4', stream_event: defaults.streamEvent }, resequencer, config: { outputFile } };
    const topology = {
      topology_name: topologyName,
      nodes: [
        { id: 'node_1', faucet: firstFaucet, worker: upper, next: [{ node: 'node_2' }], debug: generateDebug(portBase + 1) },
        { id: 'node_2', worker: appender, next: [{ node: 'node_3' }], debug: generateDebug(portBase + 2) },
        { id: 'node_3', worker: appender, next: [{ node: 'node_4' }], debug: generateDebug(portBase + 3) },
        { id: 'node_4', worker: appender, next: [], drain: fileWriter, debug: generateDebug(portBase + 4) },
      ],
    };

    /*if (useResequencer) {
      Object.keys(topology.nodes).forEach((node) => {
        Object.assign(topology.nodes[node], { use_resequencer: true });
      });
    }*/

    const pipes = pipesFactory(topology);

    pipes.startCounter();

    pipes.startNodes().then((ports) => {
      const probePort = portBase - 1;
      const srv = pipes.startProbe(probePort, () => {
        request(`http://localhost:${probePort}/topology-status`, (tErr, tResp) => {
          should.equal(null, tErr);
          tResp.statusCode.should.equal(200);
          srv.close();

          // start initial node consumption
          request(`http://localhost:${ports[0]}/open`, (err, resp) => {
            should.equal(null, err);
            resp.statusCode.should.equal(200);

            // topology ready for incoming http requests with data
            resolve(portBase);
          });
        });
      });
    });
  });
}

/**
 * RUN this test separately via: $ node_modules/mocha/bin/_mocha test/integration/chained-nodes.test.js
 */
describe('Node chain test', () => {
  it('should write single line to file', () => new Promise((resolve, reject) => {
    const outputFile = `${__dirname}/../../tmp/chained-nodes-single-message.txt`;
    prepareTopology('chained-nodes-single-message', 5501, outputFile).then((portToSendDataTo) => {
      // send data to chain of nodes via initial/first node
      const req = {
        url: `http://localhost:${portToSendDataTo}/`,
        method: 'POST',
        json: true,
        body: { data: 'hello world', settings: {} },
        headers: { job_id: 'test_123', sequence_id: 1 },
      };
      request(req, (dateErr, dataResp) => {
        should.equal(null, dateErr);
        dataResp.statusCode.should.equal(200);
      });

      // Success is when the output is written to file in 1s
      setTimeout(() => {
        chaiFile(outputFile).should.equal(`HELLO WORLD!!!${os.EOL}`);
        resolve();
      }, 1000);
    }).catch(reject);
  }));
  it('should write multiple lines to file', () => new Promise((resolve) => {
    const outputFile = `${__dirname}/../../tmp/chained-nodes-multiple-messages.txt`;
    prepareTopology('chained-nodes-multiple-messages', 5601, outputFile).then((portToSendDataTo) => {
      const expected = [];
      for (let i = 1; i <= 5; i += 1) {
        const req = {
          url: `http://localhost:${portToSendDataTo}/`,
          method: 'POST',
          json: true,
          body: { data: `hello world ${i}`, settings: {} },
          headers: { job_id: 'test-job', sequence_id: i },
        };
        expected.push(`HELLO WORLD ${i}!!!${os.EOL}`);
        request(req);
      }

      // Success is when the whole output is written to file in 1s
      setTimeout(() => {
        expected.forEach((line) => {
          // SHOULD contain - the order of lines is irrelevant
          chaiFile(outputFile).should.contain(line);
        });
        resolve();
      }, 1000);
    });
  }));
  it('should write multiple lines to file sorted in the same order as on input', () => new Promise((resolve) => {
    const outputFile = `${__dirname}/../../tmp/chained-nodes-multiple-messages-sorted.txt`;
    prepareTopology('chained-nodes-multiple-messages-sorted', 5701, outputFile, true).then((portToSendDataTo) => {
      let expected = '';
      for (let i = 1; i <= 5; i += 1) {
        const req = {
          url: `http://localhost:${portToSendDataTo}/`,
          method: 'POST',
          json: true,
          body: { data: `hello world ${i}`, settings: {} },
          headers: { job_id: 'test_job', sequence_id: i },
        };
        // Note: we cannot rely that request are processed in 1..5 order
        request(req);
        expected += `HELLO WORLD ${i}!!!${os.EOL}`;
      }

      // Success is when the whole output is written to file in 1s
      setTimeout(() => {
        // Content must equal - the order of lines matters
        chaiFile(outputFile).should.equal(expected);
        resolve();
      }, 1000);
    });
  }));
});
