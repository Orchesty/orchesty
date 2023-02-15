import { container, expressApp, routes } from '@orchesty/nodejs-sdk';
import CommonLoader from '@orchesty/nodejs-sdk/dist/lib/Commons/CommonLoader';
import CustomNodeRouter from '@orchesty/nodejs-sdk/dist/lib/CustomNode/CustomNodeRouter';
import Metrics from '@orchesty/nodejs-sdk/dist/lib/Metrics/Metrics';
import DatabaseClient from '@orchesty/nodejs-sdk/dist/lib/Storage/Database/Client';
import Node from '@orchesty/nodejs-sdk/dist/lib/Storage/Database/Document/Node';
import NodeRepository from '@orchesty/nodejs-sdk/dist/lib/Storage/Database/Document/NodeRepository';
import { MongoDb } from '@orchesty/nodejs-sdk/dist/lib/Storage/Mongo';
import config from './config';
import { ComparatorFilter } from './custom_node/ComparatorFilter';
import { ComparatorInvalidate } from './custom_node/ComparatorInvalidate';
import { ComparatorBuffer, ComparatorHash, ComparatorLock } from './model';
import { Comparator } from './service/comparator';
import { ComparatorBufferRepository, ComparatorHashRepository, ComparatorLockRepository } from './service/storage/repository';

export async function initialize(): Promise<void> {
    const mongo = new MongoDb(config.mongo.dsn);
    await mongo.connect();

    const hashRepository = new ComparatorHashRepository(mongo, ComparatorHash.name);

    const loader = new CommonLoader(container);
    const databaseClient = new DatabaseClient(container);
    const comparator = new Comparator(hashRepository);

    const lockRepository = new ComparatorLockRepository(mongo, ComparatorLock.name);
    const bufferRepository = new ComparatorBufferRepository(comparator, mongo, ComparatorBuffer.name);

    const nodeRepository = new NodeRepository(
        Node,
        databaseClient,
    );
    container.set(loader);
    container.set(databaseClient);
    container.set(mongo);
    container.set(new Metrics());

    container.set(hashRepository);
    container.set(lockRepository);
    container.set(bufferRepository);

    await container.get(ComparatorHashRepository).createIndices();
    await container.get(ComparatorLockRepository).createIndices();
    await container.get(ComparatorBufferRepository).createIndices();

    container.setRepository(nodeRepository);

    container.setCustomNode(new ComparatorFilter(comparator, bufferRepository, lockRepository));
    container.setCustomNode(new ComparatorInvalidate(hashRepository));

    routes.push(new CustomNodeRouter(expressApp, loader));
}
