
const winston = require('winston');

const env = process.env.NODE_ENV || 'development';

// Log only errors, but on development log all
let level = 'error';
if (env === 'development') {
  level = 'debug';
}

const myPrettyPrint = function myPrettyPrint(obj) {
  return JSON.stringify(obj, null, 2).replace(/(?:\r\n|\r|\n)/g, '');
};
const tsFormat = () => (new Date()).toLocaleTimeString();

const getLabel = (callingModule) => {
  const parts = callingModule.filename.split('/');
  return `${parts[parts.length - 2]}/${parts.pop()}`;
};

module.exports = callingModule => new (winston.Logger)({
  transports: [
    new (winston.transports.Console)({
      colorize: true,
      label: getLabel(callingModule),
      level,
      prettyPrint: myPrettyPrint,
      timestamp: tsFormat(),
    }),
  ],
});
