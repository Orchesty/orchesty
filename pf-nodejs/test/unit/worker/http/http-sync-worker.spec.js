
process.env.NODE_ENV = 'test';

const nock = require('nock');
const should = require('chai').should();

const Message = require('./../../../../src/job-message');
const resultCodes = require('./../../../../src/result-codes');
const SyncWorker = require('./../../../../src/worker/http/http-sync-worker');

// Mock http requests
nock('http://myapi.com').post('/ok').delay(20).reply(200, 'ok');
nock('http://myapi.com').post('/not-ok').delay(20).reply(400, 'not-ok');
nock('http://myapi.com').post('/ok-but-late').delay(1000).reply(200, 'ok');

/**
 * RUN these tests separately by:
 * $ node_modules/mocha/bin/_mocha test/unit/worker/http/http-sync-worker.spec.js
 */
describe('HttpSyncWorker', () => {
  it('should return message with success result', () => {
    const settings = {
      timeout: 0
    };
    const inMsg = new Message({ job_id: '123', sequence_id: 1 }, 'content');
    const worker = new SyncWorker('POST', 'http://myapi.com/ok', settings);
    return worker.processData(inMsg)
      .then((msg) => {
        msg.getJobResultCode().should.equal(resultCodes.SUCCESS, 'Should have SUCCESS result.');
        msg.getContent().should.equal('ok');
      });
  });
  it('should return message with failed result', () => {
    const settings = {
      timeout: 0
    };
    const inMsg = new Message({ job_id: '123', sequence_id: 1 }, 'content');
    const worker = new SyncWorker('POST', 'http://myapi.com/not-ok', settings);
    return worker.processData(inMsg)
      .catch((msg) => {
        msg.getJobResultCode().should.not.equal(resultCodes.SUCCESS, 'Should not have SUCCESS result.');
        msg.getJobResultCode().should.equal(resultCodes.HTTP_ERROR, 'Should have HTTP_ERROR result.');
        msg.getContent().should.equal('content');
      });
  });
  it('should return message with timeout result', () => {
    const settings = {
      timeout: 10
    };
    const inMsg = new Message({ job_id: '123', sequence_id: 1 }, 'content');
    const worker = new SyncWorker('POST', 'http://myapi.com/ok-but-late', settings);
    return worker.processData(inMsg)
      .catch((msg) => {
        msg.getJobResultCode().should.not.equal(resultCodes.SUCCESS, 'Should not have SUCCESS result.');
        msg.getJobResultCode().should.equal(resultCodes.WORKER_TIMEOUT, 'Should return WORKER_TIMEOUT result code.');
        msg.getContent().should.equal('content');
      });
  });
});
