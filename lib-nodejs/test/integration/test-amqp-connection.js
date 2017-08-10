const AmqpConnectionConfig = require('./../../src/pipes/amqp/connection-config');
const Connection = require('./../../src/pipes/amqp/connection');

const rabbitHost = process.env.RABBITMQ_HOST || 'localhost';
const rabbitPort = process.env.RABBITMQ_PORT || 5672;
const rabbitUser = process.env.RABBITMQ_USER || 'guest';
const rabbitPass = process.env.RABBITMQ_PASS || 'guest';
const rabbitVhost = process.env.RABBITMQ_VHOST || '/';
const connConf = new AmqpConnectionConfig(rabbitHost, rabbitPort, rabbitUser, rabbitPass, rabbitVhost);
const amqpConn = new Connection(connConf);

module.exports = amqpConn;
