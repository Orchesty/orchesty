import pino from 'pino';
import { config } from './config';
import DIContainer from './DIContainer/Container';
import Services from './DIContainer/Services';
import Mongo, { CollectionEnum } from './storage/mongo/Mongo';
import TimeModule from './TimeModule';
import { IApplinth, UsageStatsGenerator } from './usageStatsGenerator';

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
    const mongo = container.get<Mongo>(Services.MONGO);
    const timeModule = container.get<TimeModule>(Services.TIME_MODULE);

    const applinths = await mongo.getBillingAdminCollection(CollectionEnum.APPLINTH).find().toArray();

    const colMonthly = mongo.getBillingCollection(CollectionEnum.USAGE_STATS_MONTHLY);
    const colMetadata = mongo.getBillingCollection(CollectionEnum.USAGE_STATS_METADATA);
    const colModule = mongo.getBillingAdminCollection(CollectionEnum.MODULE);

    const usageStatsGenerator = new UsageStatsGenerator(colMonthly, timeModule);

    await usageStatsGenerator.generateForApplinths(applinths as IApplinth[], colMetadata, colMonthly, colModule, mongo);

    logger.info('done');
}

export { container };
