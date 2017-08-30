const request = require('request');
const should = require('chai').should();

const logger = require('./../../../../src/logger')(module);
const Message = require('./../../../../src/job-message');
const resultCodes = require('./../../../../src/result-codes');
const Worker = require('./../../../../src/worker/http/http-async-worker');

const settings = {
  timeout: 0,
  request: {
    url: 'http://www.hanaboso.com',
    method: 'POST',
  },
  response: {
    host: 'http://localhost',
    path: '/path',
    port: 3333,
    method: 'POST'
  },
};

const worker = new Worker(settings);

/**
 * RUN these tests separately by:
 * $ node_modules/mocha/bin/_mocha test/unit/worker/http/http-async-worker.spec.js
 */
describe('HttpAsyncWorker', () => {
  it('should return status 400, missing headers', (done) => {
    const req = {
      method: settings.response.method,
      url: `${settings.response.host}:${settings.response.port}${settings.response.path}`,
      headers: {
        job_id: '123', // missing content-type header
        sequence_id: 1,
      },
    };
    request(req, (err, response) => {
      response.statusCode.should.equal(400);
      done();
    });
  });
  it('should return message with success result when result received', (done) => {
    const msg = new Message({ job_id: '123', sequence_id: 1 }, '');
    msg.msgId = '123';

    // Init process
    worker.processData(msg)
      .then((outMsg) => {
        const expectedResult = 0;
        const expectedContent = { data: 'processed data', settings: {} };
        expectedResult.should.equal(resultCodes.SUCCESS);
        expectedContent.should.deep.equal(outMsg.open());
        done();
      })
      .catch((outMsg) => {
        logger.error(outMsg);
      });

    // Send async response
    setTimeout(() => {
      const req = {
        method: settings.response.method,
        url: `${settings.response.host}:${settings.response.port}${settings.response.path}`,
        headers: {
          job_id: '123',
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ data: 'processed data', settings: {} }),
      };
      request(req, (err, response) => {
        response.statusCode.should.equal(200);
      });
    }, 1000); // Must be delayed at by at least 1s in order to HttpAsyncWorker server could properly initialize
  });
});
