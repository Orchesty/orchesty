
class ConnectionConfig {

  constructor(host, port, user, pass, vhost) {
    this.protocol = 'amqp';
    this.heartbeat = 60;
    this.host = host || process.env.RABBITMQ_HOST || 'localhost';
    this.port = port || process.env.RABBITMQ_PORT || 5672;
    this.user = user || process.env.RABBITMQ_USER || 'guest';
    this.pass = pass || process.env.RABBITMQ_PASS || 'guest';
    this.vhost = vhost || process.env.RABBITMQ_VHOST || '/';

    this.validate();
  }

  /**
   * Returns connection url string in format amqp://user:pass@host:10000/vhost
   */
  getUrl() {
    return `${this.protocol}://${this.user}:${this.pass}@${this.host}:${this.port}${this.vhost}`;
  }

  /**
   *
   * @return {number}
   */
  getHeartbeat() {
    return this.heartbeat;
  }

  /**
   *
   * @return {string}
   */
  getHost() {
    return this.host;
  }

  /**
   *
   * @return {number}
   */
  getPort() {
    return this.port;
  }

  /**
   *
   * @return {string}
   */
  getUser() {
    return this.user;
  }

  /**
   *
   * @return {string}
   */
  getPass() {
    return this.pass;
  }

  /**
   *
   * @return {string}
   */
  getVhost() {
    return this.vhost;
  }

  /**
   * Throws error when provided invalid configuration
   */
  validate() {
    if (!this.protocol || !this.protocol.length) {
      throw new Error('Invalid AMQP protocol');
    }
    if (!this.host || !this.host.length) {
      throw new Error('Invalid AMQP host');
    }
    if (!this.port || this.port < 1) {
      throw new Error('Invalid AMQP port');
    }
    if (typeof this.user === 'undefined') {
      throw new Error('Invalid AMQP username');
    }
    if (typeof this.pass === 'undefined') {
      throw new Error('Invalid AMQP password');
    }
    if (!this.vhost || this.vhost[0] !== '/') {
      throw new Error('Invalid AMQP vhost');
    }
  }

}

module.exports = ConnectionConfig;
