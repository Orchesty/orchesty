const JobMessage = require('../job-message');
const EOL = require('os').EOL;


exports.processData = (inMsg) => {
  const headers = inMsg.getHeaders();
  const { settings } = inMsg.open();

  const headersStr = Object.keys(headers).reduce((acc, key) => `${acc}${EOL}  - ${key}: ${headers[key]}`, 'Headers:');

  const newData = [
    '--',
    `Message id: ${inMsg.getId()}`,
    `Processed at: ${Date.now()}`,
    headersStr
  ];

  /** TODO: 1. Stringify repeated in every worker
   *  TODO: 2. Calling setJobResultOK/setJobResultFailed is weird, fragile. It should create an error/success message.
   */
  const outMsg = new JobMessage(headers, JSON.stringify({ data: newData.join(EOL), settings }));
  outMsg.setJobResultOK();

  return Promise.resolve(outMsg);
};
