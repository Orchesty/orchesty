/**
 * docker-compose up -d
 * curl http://localhost:8008/open
 * curl -H "Content-Type: application/json" -H "job_id: 1" -H "sequence_id: 1" -d '{"data":"sample message","settings":[]}' http://localhost:3333
 * cat output.txt
 */

const sampleTopology = {
  topology_name: 'sample_topo',
  nodes: [
    {
      id: 'node_1',
      faucet: {
        type: 'http',
        config: {
          port: 3333
        }
      },
      worker: { type: 'uppercase' },
      next: [{ node: 'node_2' }],
      debug: { port: 8007 }
    },
    {
      id: 'node_2',
      worker: { type: 'appender', config: { suffix: ' [appended]' } },
      next: [{ node: 'node_3' }],
      use_resequencer: true,
      debug: { port: 8007 }
    },
    {
      id: 'node_3',
      worker: { type: 'file_writer', config: { file: `${process.cwd()}/output.txt` } },
      next: [],
      use_resequencer: true,
      debug: { port: 8007 }
    }
  ],
};

module.exports = sampleTopology;
