import { readFileSync } from 'fs';
import path from 'path';
import { mongo } from '../src/config/config';
import { CollectionEnum } from '../src/enums/CollectionEnum';
import Mongo from '../src/storage/mongo/Mongo';

async function createFixtureData(): Promise<void> {
    const db = new Mongo(mongo.dsn);
    await db.connect();

    try {
        await db.dropCollections();
    } catch (e) {
    }

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

    const client = readFileSync(path.resolve(__dirname, 'fixtureData/client.json')).toString();
    await db.getCloudCollection(CollectionEnum.CLIENT)
        .insertOne(JSON.parse(client));

    const address = readFileSync(path.resolve(__dirname, 'fixtureData/address.json')).toString();
    await db.getCloudCollection(CollectionEnum.ADDRESS)
        .insertOne(JSON.parse(address));

    const applinth = JSON.parse(readFileSync(path.resolve(__dirname, 'fixtureData/applinth.json')).toString());
    applinth.minPriceDate = new Date(applinth.minPriceDate);
    await db.getCloudCollection(CollectionEnum.APPLINTH)
        .insertOne(applinth);

    const cloud = JSON.parse(readFileSync(path.resolve(__dirname, 'fixtureData/cloud.json')).toString());
    cloud.startDate = new Date(cloud.startDate);
    cloud.closeDate = new Date(cloud.closeDate);
    await db.getCloudCollection(CollectionEnum.CLOUD)
        .insertOne(cloud);

    const correction = JSON.parse(readFileSync(path.resolve(__dirname, 'fixtureData/correction.json')).toString());
    correction.date = new Date(correction.date);
    await db.getCloudCollection(CollectionEnum.CORRECTION)
        .insertOne(correction);

    const module = JSON.parse(readFileSync(path.resolve(__dirname, 'fixtureData/modul.json')).toString());
    module.minPriceDate = new Date(module.minPriceDate);
    await db.getCloudCollection(CollectionEnum.MODULE)
        .insertOne(module);

    const orchesty = JSON.parse(readFileSync(path.resolve(__dirname, 'fixtureData/orchesty.json')).toString());
    orchesty.startDate = new Date(orchesty.startDate);
    await db.getCloudCollection(CollectionEnum.ORCHESTY)
        .insertOne(orchesty);

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
