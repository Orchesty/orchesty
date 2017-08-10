
const yaml = require('write-yaml');
const logger = require('./logger')(module);

const VERSION = '2';
const RABBITMQ_SKELETON = {
  image: 'rabbitmq:management-alpine',
  ports: [
    '15672:15672', '5672:5672'
  ]
};

let portIncrement = 5000;

class DockerComposeGenerator {

  /**
   * @param conn
   * @return {*}
   */
  static getContainerSkeleton(conn) {
    return {
      image: 'node:alpine',
      volumes: ['.:/usr/src/app'],
      working_dir: '/usr/src/app',
      environment: [
        'RABBITMQ_HOST=rabbitmq',
        `RABBITMQ_PORT=${conn.getPort()}`,
        `RABBITMQ_USER=${conn.getUser()}`,
        `RABBITMQ_PASS=${conn.getPass()}`,
        `RABBITMQ_VHOST=${conn.getVhost()}`
      ],
      depends_on: ['rabbitmq']
    };
  }

  /**
   * @param conn
   * @return {{image, volumes, working_dir, environment, depends_on}|*}
   */
  static getServicesSkeleton(conn) {
    const svc = DockerComposeGenerator.getContainerSkeleton(conn);
    svc.command = '/usr/src/app/bin/pipes start services';
    svc.ports = ['8007:8007'];

    return svc;
  }

  /**
   * @param conn
   * @param node
   * @return {{image, volumes, working_dir, environment, depends_on}|*}
   */
  static getNodeSkeleton(conn, node) {
    const n = DockerComposeGenerator.getContainerSkeleton(conn);
    n.command = `/usr/src/app/bin/pipes start node --id ${node.id}`;

    n.ports = [];

    // Allows us to check nodes from outside of docker-compose
    portIncrement += 1;
    n.ports.push(`${portIncrement}:${node.debug.port}`);

    // export faucet port if necessary
    if (node.faucet.port) {
      portIncrement += 1;
      n.ports.push(`${portIncrement}:${node.faucet.port}`);
    }

    // export worker port if necessary
    if (node.worker.port) {
      portIncrement += 1;
      n.ports.push(`${portIncrement}:${node.worker.port}`);
    }

    return n;
  }

  /**
   * Creates docker-compose.yml file
   *
   * @param nodes
   * @param conn
   * @param file
   */
  static generate(nodes, conn, file) {
    logger.info('Generating docker-compose file');
    const output = {};
    output.version = VERSION;
    output.services = {};
    output.services.services = DockerComposeGenerator.getServicesSkeleton(conn);
    for (const node of nodes) {
      output.services[node.id] = DockerComposeGenerator.getNodeSkeleton(conn, node);
      output.services.services.depends_on.push(node.id);
    }

    output.services.rabbitmq = RABBITMQ_SKELETON;

    // convert to yml and save to file
    yaml.sync(file, output);
    logger.info(`Docker-compose yml file generated: ${file}`);
  }

}

module.exports = DockerComposeGenerator;
