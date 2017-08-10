const chai = require('chai');
const chaiHttp = require('chai-http');
const chaiSpies = require('chai-spies');

const Node = require('../../src/node');

const should = chai.should();

chai.use(chaiHttp);
chai.use(chaiSpies);

describe('Node server', () => {
  let server;
  let mockFaucet;
  let mockSpy;

  beforeEach(() => {
    const open = () => Promise.resolve(() => {});

    mockFaucet = { open };

    mockSpy = chai.spy.on(mockFaucet, 'open');

    const mockDrain = {
      open: () => {}
    };

    const mockWorker = {
      processData: () => {}
    };

    const node = new Node('test_node_1', mockWorker, mockFaucet, mockDrain, 0, true);
    server = node.startServer();
  });

  afterEach((done) => {
    server.close(done);
    mockSpy.reset();
  });

  it('should listen on /status route', () =>
    chai.request(`http://localhost:${server.address().port}`).get('/status').catch((res) => {
      res.should.have.status(503);
    })
  );

  it('should listen on /open route', () =>
    chai.request(`http://localhost:${server.address().port}`).get('/open').then((res) => {
      res.should.have.status(200);
      mockSpy.should.have.been.called.once;
    })
  );

  it('should run faucet after open', (done) => {
    mockFaucet.open = () => Promise.resolve(done);

    chai.request(`http://localhost:${server.address().port}`).get('/open').then((res) => {
      res.should.have.status(200);
    });
  });
});
