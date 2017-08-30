
process.env.NODE_ENV = 'test';

const defaults = require('./../../../src/pipes/amqp/defaults');
const Configurator = require('./../../../src/pipes/configurator');
const should = require('chai').should();

describe('Configurator', () => {
  describe('topology name', () => {
    it('should be generated', () => {
      const cfg = new Configurator({}, defaults);
      should.exist(cfg.topologyName);
    });

    it('should be explicitly given', () => {
      const cfg = new Configurator({ topology_name: 'topology_1' }, defaults);
      cfg.topologyName.should.equal('topology_1');
    });
  });

  describe('normalizeConfig', () => {
    it('should create object config from array', () => {
      const act = Configurator.normalizeConfig(['id1', 'worker_1', ['node_2', 'node_3']]);

      act.should.be.instanceOf(Object);
      act.should.have.property('next');
      act.next.should.deep.equal([{ node: 'node_2' }, { node: 'node_3' }]);
      act.should.have.property('worker', 'worker_1');
    });
  });

  describe('configDefaultFaucet', () => {
    it('should construct queue and routing key', () => {
      const cfg = new Configurator({ topology_name: 'topology_1' }, defaults);
      const act = cfg.configDefaultFaucet('node_2');

      act.config.queue.should.deep.equal({ name: 'pipes.topology_1.node_2', options: defaults.streamQueue.options });
      act.config.routing_key.should.equal('topology_1.node_2');
    });
  });

  describe('configNodes', () => {
    it('should properly name queues and routing keys', () => {
      const topo = {
        topology_name: 't1',
        nodes: [
          ['node_1', 'worker_1', ['node_2']],
          ['node_2', 'worker_2', ['node_3', 'node_4']],
          ['node_3', 'worker_3'],
          ['node_4', 'worker_4'],
        ],
      };

      const channelMock = {
        on: () => {},
      };
      const connMock = {
        createChannelAndPrepare: () => new Promise((resolve) => { resolve(channelMock); }),
      };

      const cfg = new Configurator(topo, defaults, connMock);
      const gen = cfg.configNodes();

      const n1 = gen.next().value;
      const n2 = gen.next().value;
      const n3 = gen.next().value;
      const n4 = gen.next().value;

      should.exist(n1.faucet);
      should.exist(n2.faucet);
      should.exist(n3.faucet);
      should.exist(n4.faucet);

      n1.drain.amqSettings.following_pipes.should.have.lengthOf(1);
      n1.drain.amqSettings.following_pipes[0].routing_key.should.equal(n2.faucet.config.routing_key);

      n2.faucet.config.queue.name.should.equal('pipes.t1.node_2');
      n2.faucet.config.routing_key.should.equal('t1.node_2');
      n2.drain.amqSettings.following_pipes.should.have.lengthOf(2);
      n2.drain.amqSettings.following_pipes[0].routing_key.should.equal(n3.faucet.config.routing_key);
      n2.drain.amqSettings.following_pipes[1].routing_key.should.equal(n4.faucet.config.routing_key);

      n3.faucet.config.queue.name.should.equal('pipes.t1.node_3');
      n3.faucet.config.routing_key.should.equal('t1.node_3');
      n3.drain.amqSettings.following_pipes.should.be.empty;

      n4.faucet.config.queue.name.should.equal('pipes.t1.node_4');
      n4.faucet.config.routing_key.should.equal('t1.node_4');
      n4.drain.amqSettings.following_pipes.should.be.empty;

      gen.next().done.should.be.true;
    });
  });
});
