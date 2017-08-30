const argv = require('yargs').argv;
const assert = require('assert');

// const AbstractFaucet = require('./../../abstract-faucet');
const logger = require('./../../logger')(module);
const Message = require('./../../job-message');

function open(processData, drain) {
  assert(argv.jobId, 'Missing --jobId command line argument');
  const inMsg = new Message({ job_id: argv.jobId }, argv._[0]);
  // const inMsg = new Message({ job_id: '123' }, "{\"data\": \"example data\", \"settings\":[]}");

  logger.info('Cmd faucet ready.');

  return Promise.resolve(
    () => processData(inMsg).then(drain).catch(logger.error)
  );
}

exports.open = open;
exports.factory = () => ({ open });
