import logger from "lib-nodejs/dist/src/logger/Logger";
import { IOptions } from "lib-nodejs/dist/src/rabbitmq/Connection";
import yaml = require("write-yaml");

const VERSION = "2";
const RABBITMQ_SKELETON = {
    image: "rabbitmq:management-alpine",
    ports: [
        "15672:15672", "5672:5672",
    ],
};

let portIncrement = 5000;

export interface IServiceDefinition {
    image?: string;
    build?: string;
    volumes?: string[];
    working_dir?: string;
    environment?: string[];
    depends_on?: string[];
    command?: string | string[];
    ports?: string[];
}

export interface IDockerComposeFile {
    version: string;
    services: { [key: string]: IServiceDefinition };
}

class DockerComposeGenerator {

    public static getContainerSkeleton(conn: IOptions): IServiceDefinition {
        return {
            // image: "node:alpine",
            // volumes: [".:/usr/app"],
            build: ".",
            working_dir: "/usr/app",
            environment: [
                "RABBITMQ_HOST=rabbitmq",
                `RABBITMQ_PORT=${conn.port}`,
                `RABBITMQ_USER=${conn.user}`,
                `RABBITMQ_PASS=${conn.pass}`,
                `RABBITMQ_VHOST=${conn.vhost}`,
            ],
            depends_on: ["rabbitmq"],
        };
    }

    /**
     * @param conn
     * @return {{image, volumes, working_dir, environment, depends_on}|*}
     */
    public static getServicesSkeleton(conn: IOptions): IServiceDefinition {
        const svc = DockerComposeGenerator.getContainerSkeleton(conn);
        svc.command = "./dist/src/bin/pipes.js start services";
        svc.ports = ["8007:8007"];

        return svc;
    }

    /**
     *
     * @param {IOptions} conn
     * @param node
     * @return {IServiceDefinition}
     */
    public static getNodeSkeleton(conn: IOptions, node: any): IServiceDefinition {
        const n = DockerComposeGenerator.getContainerSkeleton(conn);
        n.command = `./dist/src/bin/pipes.js start node --id ${node.id}`;

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
    public static generate(nodes: any[], conn: IOptions, file: string): void {
        logger.info("Generating docker-compose file");

        const output: IDockerComposeFile = {
            version: VERSION,
            services: {},
        };

        // Add services service
        output.services.services = DockerComposeGenerator.getServicesSkeleton(conn);

        // Add node services
        for (const node of nodes) {
            output.services[node.id] = DockerComposeGenerator.getNodeSkeleton(conn, node);
            output.services.services.depends_on.push(node.id);
        }

        // Add rabbitmq service
        output.services.rabbitmq = RABBITMQ_SKELETON;

        // convert to yml and save to file
        yaml.sync(file, output);
        logger.info(`Docker-compose yml file generated: ${file}`);
    }

}

export default DockerComposeGenerator;
