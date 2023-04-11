import { program } from 'commander';
import { ObjectId } from 'mongodb';
import pino from 'pino';
import { config } from './config';
import { EventFactory, RawEvent } from './EventFactory';
import { USEvent } from './events';
import { persist, PersisterMode, upsertMetadata } from './persister';
import { Processor } from './processor';
import { BillingAdminStorage } from './storage/BillingAdminStorage';
import { BillingStorage } from './storage/BillingStorage';
import { MongoDBConn } from './storage/MongoDBConn';
import { UsageStatsStorage } from './storage/UsageStatsStorage';

process.env.TZ = 'UTC';

/** Single point of truth for NOW to make the whole app more deterministic. */
export const now = Date.now();

// cli

enum Commands {
    NONE = 'NONE',
    ALL = 'ALL',
}

let command: Commands = Commands.NONE;
let dryRun = false;

program
    .option('--json-logs', 'enable structured JSON logging')
    .option('--log-level <error|warn|info|debug>', 'log level constraint', 'info');

program
    .command('all')
    .description('Generate billing data for all instances')
    .option('--dry-run', 'do not write any data to database, only pretend')
    .action((opts) => {
        command = Commands.ALL;
        ({ dryRun } = opts);
    });

program.parse();

const { jsonLogs } = program.opts();

// setup logging

export const logger = pino(
    jsonLogs ? {
        base: undefined,
    }
        : {
            transport: {
                target: 'pino-pretty',
            },
            timestamp: false,
            base: undefined,
        },
);

logger.level = program.opts().logLevel;

// setup storage

const mongoConn = new MongoDBConn(config.mongodbDSN);
const usdb = new UsageStatsStorage(mongoConn, config);
const bdb = new BillingStorage(mongoConn, config);
const badb = new BillingAdminStorage(mongoConn, config);

const eventFactory = new EventFactory();

const processor = new Processor();

async function getEvents(instanceId: string, lastHighestDateTimestamp?: string): Promise<USEvent[]> {
    const db = await usdb.db();
    const coll = db.collection('Events');
    const filter = lastHighestDateTimestamp ? { created: { $gt: lastHighestDateTimestamp } } : {};
    const res = coll.find({ ...filter, iid: instanceId }).sort({ created: 1 });

    const events = [];

    for await (const doc of res) {
        if (doc.type !== null && doc.type !== 'applinth_enduser_app_hearthbeat') {
            const re: RawEvent = {
                version: 0,
                type: '',
                ...doc,
                created: new Date(parseInt(doc.created, 10) / 1000),
            };
            delete re._id;

            events.push(eventFactory.create(re));
        }
    }

    return events;
}

async function commandAll(): Promise<void> {
    const billingDb = await badb.db();
    const applinths = await billingDb.collection('applinth').find().toArray();
    const db = await bdb.db();

    const colMonthly = db.collection('usage_stats_monthly');
    const colMetadata = db.collection('usage_stats_metadata');
    const colModule = billingDb.collection('module');

    for (const applinth of applinths) {
        // eslint-disable-next-line no-await-in-loop
        const metadata = (await colMetadata.findOne(
            { tenantId: applinth.tenantId },
            { projection: { [`instances.${applinth.instanceId}`]: 1 } },
        ))?.instances[applinth.instanceId];

        const lastHighestDateTimestamp = metadata ? metadata.lastRunHighestEventTimestamp : null;

        // eslint-disable-next-line no-await-in-loop
        const events = await getEvents(applinth.instanceId, lastHighestDateTimestamp);
        // eslint-disable-next-line no-await-in-loop
        await processor.process(events);
        const newestEvent = events[events.length - 1];
        let highestDate = lastHighestDateTimestamp;
        if (newestEvent) {
            highestDate = (newestEvent.created.valueOf() * 1000).toString();
        }

        // eslint-disable-next-line no-await-in-loop
        const billingDocs = await processor.monthlyAll(
            applinth as { _id: ObjectId; tenantId: string; instanceId: string },
            colModule,
        );

        // TODO rich vygenerovani distinct modulu
        // logger.info(JSON.stringify((billingDocs as unknown as { appId: string }[]).map((item) => ({
        //     appName: item.appId,
        //     applinthId: '642163a885994c0914a280a5',
        //     price: 19900000,
        // }))));

        // eslint-disable-next-line no-await-in-loop
        await persist(
            billingDocs,
            colMonthly,
            dryRun ? PersisterMode.DRY_RUN : PersisterMode.GENERATE,
            applinth.instanceId,
            lastHighestDateTimestamp,
        );
        // eslint-disable-next-line no-await-in-loop
        await upsertMetadata(colMetadata, applinth.tenantId, applinth.instanceId, highestDate);
    }

    /*
    const hourly = processor.rangeWithGranularity(start, end, 1, constFields);

    console.log('-----------------------');

    const daily = processor.rangeWithGranularity(start, end, 24, constFields);

    const colDaily = (await bdb.db()).collection('usage_stats_daily');
     //*   await colDaily.deleteMany({});
    for (const doc of daily) {
     //*           await colDaily.insertOne(doc as any);
    }

    // const colHourly = (await bdb.db()).collection('usage_stats_hourly');
    // await colHourly.deleteMany({});
    // for (const doc of hourly) {
    //    await colHourly.insertOne(doc);
    // }

    // console.log([...processor.flatten(new Date('2021-09-13T10:00:00Z'), new Date('2021-09-14T03:00:00Z'),{
    //     instanceId: 'i1234',
    //     tenantId: 't6789',
    // })]);

*/
    logger.info('done');
}

(async function() {
    switch (command as Commands) {
        case Commands.ALL:
            await commandAll();
            break;
        default:
    }

    logger.debug({ memoryUsage: process.memoryUsage() });
    await mongoConn.close();
}()).catch((e) => {
    logger.error(e);
});
