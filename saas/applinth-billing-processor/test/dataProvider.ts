import { readFileSync } from 'fs';
import { ObjectId } from 'mongodb';
import path from 'path';
import { container, logger } from '../src';
import Services from '../src/DIContainer/Services';
import { UsageStatsType } from '../src/enums/UsageStatsType';
import { IUsageStatsMonthly } from '../src/processor/BaseProcessor';
import { IApplinth } from '../src/processor/EndUserAppInstallProcessor';
import Mongo, { CollectionEnum } from '../src/storage/mongo/Mongo';

export async function createFixtureData(createEvents = true): Promise<void> {
    const mongo = container.get<Mongo>(Services.MONGO);
    await mongo.dropCollections();

    if (createEvents) {
        logger.info('Creating Events');
        const event = readFileSync(path.resolve(__dirname, 'fixtureData/Events.json')).toString();
        await mongo.getUsageStatsCollection(CollectionEnum.EVENTS).insertMany(JSON.parse(event));
    }

    logger.info('Creating applinth');
    const applinths = JSON.parse(readFileSync(path.resolve(__dirname, 'fixtureData/applinth.json')).toString()) as IApplinth[];
    await mongo.getBillingAdminCollection(CollectionEnum.APPLINTH).insertMany(applinths.map((item) => {
        item._id = new ObjectId(item._id);
        item.minPriceDate = new Date(item.minPriceDate as unknown as string);
        return item;
    }));

    logger.info('Creating module');
    const modules = JSON.parse(readFileSync(path.resolve(__dirname, 'fixtureData/module.json')).toString());
    await mongo.getBillingAdminCollection(CollectionEnum.MODULE).insertMany(modules.map(
        (item: { minPriceDate: Date }) => {
            item.minPriceDate = new Date(item.minPriceDate as unknown as string);
            return item;
        },
    ));

    logger.info('Creating cloud');
    const clouds = JSON.parse(readFileSync(path.resolve(__dirname, 'fixtureData/cloud.json')).toString());
    await mongo.getBillingAdminCollection(CollectionEnum.CLOUD).insertMany(clouds.map(
        (item: { startDate: Date }) => {
            item.startDate = new Date(item.startDate as unknown as string);
            return item;
        },
    ));

    logger.info('Creating orchesty');
    const orchesty = JSON.parse(readFileSync(path.resolve(__dirname, 'fixtureData/orchesty.json')).toString());
    await mongo.getBillingAdminCollection(CollectionEnum.ORCHESTY).insertMany(orchesty.map(
        (item: { startDate: Date }) => {
            item.startDate = new Date(item.startDate as unknown as string);
            return item;
        },
    ));
}

export function generateExpectedUsageStats(): IUsageStatsMonthlyForTesting[] {
    return [
        {
            appId: 'shoptet',
            start: '2023-01-01T03:00:00.000Z',
            end: '2023-01-03T03:00:00.000Z',
            endUserId: '1',
            type: UsageStatsType.ENDUSER_APP_INSTALL,
            installId: 'AID1672542000000',
            instanceId: 'iid1',
            tenantId: 't1',
            installed: false,
            cost: 6,
        },
        {
            appId: 'shoptet',
            start: '2023-01-05T03:00:00.000Z',
            end: '2023-02-01T00:00:00.000Z',
            endUserId: '1',
            type: UsageStatsType.ENDUSER_APP_INSTALL,
            installId: 'AID1672887600000',
            instanceId: 'iid1',
            tenantId: 't1',
            installed: false,
            cost: 87,
        },
        {
            appId: 'shoptet',
            start: '2023-02-01T00:00:00.000Z',
            end: '2023-02-05T01:00:00.000Z',
            endUserId: '1',
            type: UsageStatsType.ENDUSER_APP_INSTALL,
            installId: 'AID1672887600000',
            instanceId: 'iid1',
            tenantId: 't1',
            installed: false,
            cost: 18,
        },
        {
            appId: 'shoptet',
            start: '2023-03-05T01:00:00.000Z',
            end: '2023-03-10T01:00:00.000Z',
            endUserId: '1',
            type: UsageStatsType.ENDUSER_APP_INSTALL,
            installId: 'AID1677978000000',
            instanceId: 'iid1',
            tenantId: 't1',
            installed: false,
            cost: 16,
        },
        {
            appId: 'shoptet',
            start: '2023-03-12T05:00:00.000Z',
            end: '2023-04-01T00:00:00.000Z',
            endUserId: '2',
            type: UsageStatsType.ENDUSER_APP_INSTALL,
            installId: 'AID1678597200000',
            instanceId: 'iid1',
            tenantId: 't1',
            installed: false,
            cost: 65,
        },
        {
            appId: 'shoptet',
            start: '2023-04-01T00:00:00.000Z',
            end: '2023-04-05T00:00:00.000Z',
            endUserId: '2',
            type: UsageStatsType.ENDUSER_APP_INSTALL,
            installId: 'AID1678597200000',
            instanceId: 'iid1',
            tenantId: 't1',
            installed: true,
            cost: 13,
            estimatedCost: 100,
        },
        {
            appId: 'shoptet',
            start: '2023-03-15T01:00:00.000Z',
            end: '2023-04-01T00:00:00.000Z',
            endUserId: '1',
            type: UsageStatsType.ENDUSER_APP_INSTALL,
            installId: 'AID1678842000000',
            instanceId: 'iid1',
            tenantId: 't1',
            installed: false,
            cost: 55,
        },
        {
            appId: 'shoptet',
            start: '2023-04-01T00:00:00.000Z',
            end: '2023-04-05T00:00:00.000Z',
            endUserId: '1',
            type: UsageStatsType.ENDUSER_APP_INSTALL,
            installId: 'AID1678842000000',
            instanceId: 'iid1',
            tenantId: 't1',
            installed: true,
            cost: 13,
            estimatedCost: 100,
        },
        {
            appId: 'woocommerce',
            start: '2023-03-12T05:00:00.000Z',
            end: '2023-04-01T00:00:00.000Z',
            endUserId: '3',
            type: UsageStatsType.ENDUSER_APP_INSTALL,
            installId: 'AID1678597200000',
            instanceId: 'iid1',
            tenantId: 't1',
            installed: false,
            cost: 129,
        },
        {
            appId: 'woocommerce',
            start: '2023-04-01T00:00:00.000Z',
            end: '2023-04-05T00:00:00.000Z',
            endUserId: '3',
            type: UsageStatsType.ENDUSER_APP_INSTALL,
            installId: 'AID1678597200000',
            instanceId: 'iid1',
            tenantId: 't1',
            installed: true,
            cost: 27,
            estimatedCost: 200,
        },
        {
            appId: 'shoptet',
            start: '2023-03-05T01:00:00.000Z',
            end: '2023-03-10T01:00:00.000Z',
            endUserId: '4',
            type: UsageStatsType.ENDUSER_APP_INSTALL,
            installId: 'AID1677978000000',
            instanceId: 'iid2',
            tenantId: 't1',
            installed: false,
            cost: 48,
        },
        {
            appId: 'shoptet',
            start: '2023-03-15T01:00:00.000Z',
            end: '2023-04-01T00:00:00.000Z',
            endUserId: '4',
            type: UsageStatsType.ENDUSER_APP_INSTALL,
            installId: 'AID1678842000000',
            instanceId: 'iid2',
            tenantId: 't1',
            installed: false,
            cost: 165,
        },
        {
            appId: 'shoptet',
            start: '2023-04-01T00:00:00.000Z',
            end: '2023-04-05T00:00:00.000Z',
            endUserId: '4',
            type: UsageStatsType.ENDUSER_APP_INSTALL,
            installId: 'AID1678842000000',
            instanceId: 'iid2',
            tenantId: 't1',
            installed: true,
            cost: 40,
            estimatedCost: 300,
        },
        {
            appId: 'shoptet',
            start: '2023-02-28T05:00:00.000Z',
            end: '2023-03-01T00:00:00.000Z',
            endUserId: '5',
            type: UsageStatsType.ENDUSER_APP_INSTALL,
            installId: 'AID1677560400000',
            instanceId: 'iid3',
            tenantId: 't2',
            installed: false,
            cost: 14,
        },
        {
            appId: 'shoptet',
            start: '2023-03-01T00:00:00.000Z',
            end: '2023-03-10T01:00:00.000Z',
            endUserId: '5',
            type: UsageStatsType.ENDUSER_APP_INSTALL,
            installId: 'AID1677560400000',
            instanceId: 'iid3',
            tenantId: 't2',
            installed: false,
            cost: 129,
        },
        {
            appId: 'shoptet',
            start: '2023-03-15T01:00:00.000Z',
            end: '2023-04-01T00:00:00.000Z',
            endUserId: '5',
            type: UsageStatsType.ENDUSER_APP_INSTALL,
            installId: 'AID1678842000000',
            instanceId: 'iid3',
            tenantId: 't2',
            installed: false,
            cost: 219,
        },
        {
            appId: 'shoptet',
            start: '2023-04-01T00:00:00.000Z',
            end: '2023-04-05T00:00:00.000Z',
            endUserId: '5',
            type: UsageStatsType.ENDUSER_APP_INSTALL,
            installId: 'AID1678842000000',
            instanceId: 'iid3',
            tenantId: 't2',
            installed: true,
            cost: 53,
            estimatedCost: 400,
        },
        {
            appId: 'shoptet',
            cost: 49986,
            end: '2023-03-01T00:00:00.000Z',
            instanceId: 'iid3',
            start: '2023-02-01T00:00:00.000Z',
            tenantId: 't2',
            type: UsageStatsType.MIN_PRICE_DIFF,
        },
        {
            appId: 'shoptet',
            cost: 49652,
            end: '2023-04-01T00:00:00.000Z',
            instanceId: 'iid3',
            start: '2023-03-01T00:00:00.000Z',
            tenantId: 't2',
            type: UsageStatsType.MIN_PRICE_DIFF,
        },
        {
            appId: 'woocommerce',
            start: '2023-02-28T05:00:00.000Z',
            end: '2023-03-01T00:00:00.000Z',
            endUserId: '5',
            type: UsageStatsType.ENDUSER_APP_INSTALL,
            installId: 'AID1677560400000',
            instanceId: 'iid3',
            tenantId: 't2',
            installed: false,
            cost: 14,
        },
        {
            appId: 'woocommerce',
            start: '2023-03-01T00:00:00.000Z',
            end: '2023-03-10T01:00:00.000Z',
            endUserId: '5',
            type: UsageStatsType.ENDUSER_APP_INSTALL,
            installId: 'AID1677560400000',
            instanceId: 'iid3',
            tenantId: 't2',
            installed: false,
            cost: 129,
        },
        {
            appId: 'woocommerce',
            start: '2023-03-15T01:00:00.000Z',
            end: '2023-04-01T00:00:00.000Z',
            endUserId: '5',
            type: UsageStatsType.ENDUSER_APP_INSTALL,
            installId: 'AID1678842000000',
            instanceId: 'iid3',
            tenantId: 't2',
            installed: false,
            cost: 219,
        },
        {
            appId: 'woocommerce',
            start: '2023-04-01T00:00:00.000Z',
            end: '2023-04-05T00:00:00.000Z',
            endUserId: '5',
            type: UsageStatsType.ENDUSER_APP_INSTALL,
            installId: 'AID1678842000000',
            instanceId: 'iid3',
            tenantId: 't2',
            installed: true,
            cost: 53,
            estimatedCost: 400,
        },
        {
            appId: 'woocommerce',
            cost: 9986,
            end: '2023-03-01T00:00:00.000Z',
            instanceId: 'iid3',
            start: '2023-02-01T00:00:00.000Z',
            tenantId: 't2',
            type: UsageStatsType.MIN_PRICE_DIFF,
        },
        {
            appId: 'woocommerce',
            cost: 9652,
            end: '2023-04-01T00:00:00.000Z',
            instanceId: 'iid3',
            start: '2023-03-01T00:00:00.000Z',
            tenantId: 't2',
            type: UsageStatsType.MIN_PRICE_DIFF,
        },
        {
            appId: 'shopify',
            start: '2023-02-28T05:00:00.000Z',
            end: '2023-03-01T00:00:00.000Z',
            endUserId: '5',
            type: UsageStatsType.ENDUSER_APP_INSTALL,
            installId: 'AID1677560400000',
            instanceId: 'iid3',
            tenantId: 't2',
            installed: false,
            cost: 14,
        },
        {
            appId: 'shopify',
            start: '2023-03-01T00:00:00.000Z',
            end: '2023-03-10T01:00:00.000Z',
            endUserId: '5',
            type: UsageStatsType.ENDUSER_APP_INSTALL,
            installId: 'AID1677560400000',
            instanceId: 'iid3',
            tenantId: 't2',
            installed: false,
            cost: 129,
        },
        {
            appId: 'shopify',
            start: '2023-03-15T01:00:00.000Z',
            end: '2023-04-01T00:00:00.000Z',
            endUserId: '5',
            type: UsageStatsType.ENDUSER_APP_INSTALL,
            installId: 'AID1678842000000',
            instanceId: 'iid3',
            tenantId: 't2',
            installed: false,
            cost: 219,
        },
        {
            appId: 'shopify',
            start: '2023-04-01T00:00:00.000Z',
            end: '2023-04-05T00:00:00.000Z',
            endUserId: '5',
            type: UsageStatsType.ENDUSER_APP_INSTALL,
            installId: 'AID1678842000000',
            instanceId: 'iid3',
            tenantId: 't2',
            installed: true,
            cost: 53,
            estimatedCost: 400,
        },
        {
            appId: 'shopify',
            cost: 9986,
            end: '2023-03-01T00:00:00.000Z',
            instanceId: 'iid3',
            start: '2023-02-01T00:00:00.000Z',
            tenantId: 't2',
            type: UsageStatsType.MIN_PRICE_DIFF,
        },
        {
            appId: 'shopify',
            cost: 9652,
            end: '2023-04-01T00:00:00.000Z',
            instanceId: 'iid3',
            start: '2023-03-01T00:00:00.000Z',
            tenantId: 't2',
            type: UsageStatsType.MIN_PRICE_DIFF,
        },
    ];
}

export async function generateEventsForSplitImport1(): Promise<void> {
    logger.info('Creating Events');
    const mongo = container.get<Mongo>(Services.MONGO);
    await mongo.getUsageStatsCollection(CollectionEnum.EVENTS).insertMany([
        {
            created: '1672563135000000',
            data: {
                aid: 'shoptet',
                euid: '1',
            },
            iid: 'iid1',
            type: 'applinth_enduser_app_install',
            version: 1,
        },
        {
            created: '1672908735000000',
            data: {
                aid: 'shoptet',
                euid: '1',
            },
            iid: 'iid1',
            type: 'applinth_enduser_app_uninstall',
            version: 1,
        },
        {
            created: '1673772735000000',
            data: {
                aid: 'shoptet',
                euid: '1',
            },
            iid: 'iid1',
            type: 'applinth_enduser_app_install',
            version: 1,
        },
        {
            created: '1676451135000000',
            data: {
                aid: 'shoptet',
                euid: '1',
            },
            iid: 'iid1',
            type: 'applinth_enduser_app_uninstall',
            version: 1,
        },
        {
            created: '1676623935000000',
            data: {
                aid: 'shoptet',
                euid: '1',
            },
            iid: 'iid1',
            type: 'applinth_enduser_app_install',
            version: 1,
        },
        {
            created: '1676969535000000',
            data: {
                aid: 'shoptet',
                euid: '1',
            },
            iid: 'iid1',
            type: 'applinth_enduser_app_uninstall',
            version: 1,
        },
        {
            created: '1677315135000000',
            data: {
                aid: 'shoptet',
                euid: '1',
            },
            iid: 'iid1',
            type: 'applinth_enduser_app_install',
            version: 1,
        },
        {
            created: '1672542000000000',
            iid: 'iid1',
            type: 'cloud_install',
            version: 1,
        },
        {
            created: '1672714800000000',
            iid: 'iid1',
            type: 'cloud_uninstall',
            version: 1,
        },
        {
            created: '1677978000000000',
            iid: 'iid1',
            type: 'cloud_install',
            version: 1,
        },
        {
            created: '1673614162000000',
            data: {
                day: '2022-06-02',
                total: 5,
            },
            iid: 'iid1',
            type: 'orchesty_operations',
            version: 1,
        },
        {
            created: '1673959762000000',
            data: {
                day: '2022-06-05',
                total: 7,
            },
            iid: 'iid1',
            type: 'orchesty_operations',
            version: 1,
        },
        {
            created: '1678842000000000',
            data: {
                day: '2022-06-02',
                total: 3,
            },
            iid: 'iid1',
            type: 'orchesty_operations',
            version: 1,
        },
        {
            created: '1679011200000000',
            data: {
                day: '2022-06-05',
                total: 10,
            },
            iid: 'iid1',
            type: 'orchesty_operations',
            version: 1,
        },
    ]);
}

export async function generateEventsForSplitImport2(): Promise<void> {
    logger.info('Creating Events');
    const mongo = container.get<Mongo>(Services.MONGO);
    await mongo.getUsageStatsCollection(CollectionEnum.EVENTS).insertMany([
        {
            created: '1685601942000000',
            data: {
                aid: 'shoptet',
                euid: '1',
            },
            iid: 'iid1',
            type: 'applinth_enduser_app_install',
            version: 1,
        },
        {
            created: '1685947542000000',
            data: {
                aid: 'shoptet',
                euid: '1',
            },
            iid: 'iid1',
            type: 'applinth_enduser_app_uninstall',
            version: 1,
        },
        {
            created: '1688280342000000',
            data: {
                aid: 'shoptet',
                euid: '1',
            },
            iid: 'iid1',
            type: 'applinth_enduser_app_install',
            version: 1,
        },
        {
            created: '1689835542000000',
            data: {
                aid: 'shoptet',
                euid: '1',
            },
            iid: 'iid1',
            type: 'applinth_enduser_app_uninstall',
            version: 1,
        },
        {
            created: '1690267542000000',
            data: {
                aid: 'shoptet',
                euid: '1',
            },
            iid: 'iid1',
            type: 'applinth_enduser_app_install',
            version: 1,
        },
        {
            created: '1690440342000000',
            data: {
                aid: 'shoptet',
                euid: '1',
            },
            iid: 'iid1',
            type: 'applinth_enduser_app_uninstall',
            version: 1,
        },
        {
            created: '1690613142000000',
            data: {
                aid: 'shoptet',
                euid: '1',
            },
            iid: 'iid1',
            type: 'applinth_enduser_app_install',
            version: 1,
        },
        {
            created: '1690879935000000',
            iid: 'iid1',
            type: 'cloud_uninstall',
            version: 1,
        },
        {
            created: '1691657535000000',
            data: {
                day: '2022-06-02',
                total: 3,
            },
            iid: 'iid1',
            type: 'orchesty_operations',
            version: 1,
        },
        {
            created: '1692089535000000',
            data: {
                day: '2022-06-05',
                total: 10,
            },
            iid: 'iid1',
            type: 'orchesty_operations',
            version: 1,
        },
    ]);
}

interface IUsageStatsMonthlyForTesting extends Omit<IUsageStatsMonthly, 'end' | 'start'> {
    start: string;
    end: string;
}
