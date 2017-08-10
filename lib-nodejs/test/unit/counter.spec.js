
process.env.NODE_ENV = 'test';

const should = require('chai').should();

const Counter = require('./../../src/counter');
const logger = require('./../../src/logger')(module);

const createCounterInstance = () => {
  const counterSettings = {
    sub: { queue: { name: 'sub_q', prefetch: 1, options: {} } },
    pub: { exchange: { name: 'pub_e', type: 'direct', options: {} }, routing_key: 'pub_rk' },
  };
  const channelMock = {
    ack: () => {},
    assertQueue: () => {},
    assertExchange: () => {},
    on: () => {},
    prefetch: () => {},
    publish: () => {},
  };
  const upConn = {
    createChannel: () => new Promise((resolve) => { resolve(channelMock); }),
  };
  const conn = {
    connect: () => new Promise((resolve) => { resolve(upConn); }),
    createChannelAndPrepare: () => new Promise((resolve) => { resolve(channelMock); }),
  };

  return new Counter(counterSettings, conn);
};

/**
 * RUN these tests separately via: $ node_modules/mocha/bin/_mocha test/unit/counter.spec.js
 */
describe('Counter', () => {
  describe('common methods', () => {
    it('isJobFinished should count total and compare with saved total', () => {
      let job = { ok: 5, nok: 2, total: 8 };
      Counter._isJobFinished(job).should.be.false;

      job = { ok: 6, nok: 2, total: 8 };
      Counter._isJobFinished(job).should.be.true;
    });
    it('updateJob should update OK, NOK and TOTAL counts', () => {
      let job = Counter._createJob('abc');
      job = Counter._updateJob(job, 0, 1, 1, null);
      job.should.deep.equal({ id: 'abc', total: 2, ok: 1, nok: 0, messages: [] });
      job = Counter._updateJob(job, 1, 2, 1, null);
      job.should.deep.equal({ id: 'abc', total: 4, ok: 1, nok: 1, messages: [] });
      job = Counter._updateJob(job, 0, 1, 2, null);
      job.should.deep.equal({ id: 'abc', total: 6, ok: 2, nok: 1, messages: [] });
      job = Counter._updateJob(job, 0, 0);
      job = Counter._updateJob(job, 0, 0);
      job = Counter._updateJob(job, 22, 0);
      job.should.deep.equal({ id: 'abc', total: 6, ok: 4, nok: 2, messages: [] });
      should.equal(true, Counter._isJobFinished(job));
    });
    it('handleMessage should prepare all variables for handleJob', () => {
      const counter = createCounterInstance();
      counter.handleJob = (jobId, status, nextCount, multiplier, error) => {
        should.equal('abc', jobId);
        should.equal(0, status);
        should.equal(2, nextCount);
        should.equal(1, multiplier);
        should.equal(null, error);
      };
      const content = { result: { code: 0, message: '' }, route: { following: 2, multiplier: 1 } };
      const buf = new Buffer(JSON.stringify(content));
      const msg = { properties: { headers: { job_id: 'abc.def', node_id: 'node_1' } }, content: buf };
      counter._handleMessage(msg);
    });
    it('handleMessage should prepare all variables for handleJob and add error', () => {
      const counter = createCounterInstance();
      counter.handleJob = (jobId, status, nextCount, multiplier, error) => {
        should.equal('abc', jobId);
        should.equal(404, status);
        should.equal(1, nextCount);
        should.equal(99, multiplier);
        should.not.equal(null, error);
        error.should.deep.equal({ resultCode: 404, node: 'node_1', message: 'Some error message' });
      };
      const content = { result: { code: 404, message: 'Some error message' }, route: { following: 1, multiplier: 99 } };
      const buf = new Buffer(JSON.stringify(content));
      const msg = { properties: { headers: { job_id: 'abc', node_id: 'node_1' } }, content: buf };
      counter._handleMessage(msg);
    });
    it('handleJob should keep the job status data and call onJobFinished', () => {
      const finishedJobs = [];
      const counter = createCounterInstance();
      counter._onJobFinished = (job) => {
        finishedJobs.push(job.id);
      };

      counter._handleJob('abc', 0, 1, 1, null);
      counter._handleJob('xyz', 0, 2, 1, null);
      finishedJobs.should.be.empty;

      counter._handleJob('xyz', 0, 0, 1, null);
      counter._handleJob('abc', 0, 1, 1, null);
      finishedJobs.should.be.empty;

      // xyz finishes by following line
      counter._handleJob('xyz', 404, 0, 1, null);
      finishedJobs.should.be.deep.equal(['xyz']);
      // abc finishes by following line
      counter._handleJob('abc', 0, 0, 1, null);

      finishedJobs.should.be.deep.equal(['xyz', 'abc']);
    });
  });
});
