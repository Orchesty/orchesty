import { readFileSync } from 'fs';
import path from 'path';
import { mongo } from '../src/config/config';
import { CollectionEnum } from '../src/enums/CollectionEnum';
import Mongo from '../src/storage/mongo/Mongo';

async function createFixtureData(): Promise<void> {
    const db = new Mongo(mongo.dsn);
    await db.connect();

    await db.dropCollections();
    await db.createBillingIndexes();
    await db.createCloudIndexes();

    const tenant = readFileSync(path.resolve(__dirname, 'fixtureData/tenant.json')).toString();
    await db.getCloudCollection(CollectionEnum.TENANT).insertOne(JSON.parse(tenant));

    const usageStatsHourly = readFileSync(path.resolve(__dirname, 'fixtureData/usage_stats_hourly.json')).toString();
    await db.getBillingCollection(CollectionEnum.USAGE_STATS_HOURLY)
        .insertMany(JSON.parse(usageStatsHourly)
            .map((item: IUsageStat) => ({
                ...item,
                start: new Date(item.start),
                end: new Date(item.end),
            })));
    const usageStatsDaily = readFileSync(path.resolve(__dirname, 'fixtureData/usage_stats_daily.json')).toString();
    await db.getBillingCollection(CollectionEnum.USAGE_STATS_DAILY)
        .insertMany(JSON.parse(usageStatsDaily)
            .map((item: IUsageStat) => ({
                ...item,
                start: new Date(item.start),
                end: new Date(item.end),
            })));
    const usageStatsMonthly = readFileSync(path.resolve(__dirname, 'fixtureData/usage_stats_monthly.json')).toString();
    await db.getBillingCollection(CollectionEnum.USAGE_STATS_MONTHLY)
        .insertMany(JSON.parse(usageStatsMonthly)
            .map((item: IUsageStat) => ({
                ...item,
                start: new Date(item.start),
                end: new Date(item.end),
            })));
    const usageStatsMetadata = readFileSync(path.resolve(__dirname, 'fixtureData/usage_stats_metadata.json')).toString();
    await db.getBillingCollection(CollectionEnum.USAGE_STATS_METADATA)
        .insertMany(JSON.parse(usageStatsMetadata)
            .map((item: IUsageStatMetadata) => ({
                ...item,
                billingHistoryStart: new Date(item.billingHistoryStart),
                billingHistoryEnd: new Date(item.billingHistoryEnd),
            })));

    await db.disconnect();
}

// eslint-disable-next-line
createFixtureData().then(() => {
    process.exit();
});

interface IUsageStat {
    start: string;
    end: string;
}

interface IUsageStatMetadata {
    billingHistoryStart: string;
    billingHistoryEnd: string;
}
