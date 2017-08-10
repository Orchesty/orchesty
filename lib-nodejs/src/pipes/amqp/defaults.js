const moniker = require('moniker');

const topologyName = moniker.choose();
const streamExchange = { name: 'pipes.stream', type: 'direct', options: {} };
const eventsExchange = { name: `pipes.${topologyName}.events`, type: 'direct', options: {} };

exports.streamEvent = { exchange: streamExchange, routing_key: 'stream-event' };
exports.counter = {
  pub: {
    exchange: eventsExchange,
    routing_key: 'job_done',
  },
  sub: {
    queue: { name: 'pipes.counter', prefetch: 1, options: {} },
    exchange: eventsExchange,
  },
};

exports.topologyName = topologyName;
exports.streamQueue = {
  prefetch: 1,
  options: {},
};
exports.streamExchange = streamExchange;
exports.eventsExchange = eventsExchange;
exports.deadLetterExchange = { name: 'pipes.dead-letter', type: 'direct', options: {} };
