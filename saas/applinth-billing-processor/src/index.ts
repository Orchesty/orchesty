import pino from 'pino';
import { config } from './config';
import DIContainer from './DIContainer/Container';
import Services from './DIContainer/Services';
import { CloudInstallProcessor } from './processor/CloudInstallProcessor';
import { EndUserAppInstallProcessor } from './processor/EndUserAppInstallProcessor';
import { OrchestyOperationsProcessor } from './processor/OrchestyOperationsProcessor';
import { ProcessorManager } from './processor/ProcessorManager';
import Mongo from './storage/mongo/Mongo';
import TimeModule from './TimeModule';

export const logger = pino({ level: config.debug ? 'debug' : 'info' });

const container = new DIContainer();

export async function initServices(): Promise<void> {
    const mongo = new Mongo(config.mongodbDSN);
    await mongo.connect();

    logger.info('MongoDB connected!');
    container.set(Services.MONGO, mongo);

    const timeModule = new TimeModule();
    container.set(Services.TIME_MODULE, timeModule);
}

export async function command(): Promise<void> {
    const processorManager = new ProcessorManager();

    processorManager.registerProcessor([
        new EndUserAppInstallProcessor(),
        new CloudInstallProcessor(),
        new OrchestyOperationsProcessor(),
    ]);
    await processorManager.process();

    logger.info('done');
}

export { container };
