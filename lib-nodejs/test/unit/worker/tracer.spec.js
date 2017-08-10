process.env.NODE_ENV = 'test';

const should = require('chai').should();

const JobMessage = require('../../../src/job-message');
const worker = require('../../../src/worker/tracer');

const sampleMessage = new JobMessage({ job_id: 1, sequence_id: 1 }, JSON.stringify({ data: 'Sample message', settings: []}));

describe('Tracer worker', () => {
  it('should processMessage', () => {
    worker.processData(sampleMessage).then((out) => {
      out.getJobId().should.equal(1);
      out.open().data.indexOf('Message id').should.not.equal(-1);
      out.open().data.indexOf('Processed at').should.not.equal(-1);
      out.open().data.indexOf('Headers').should.not.equal(-1);
    });
  });
});
