import assert from 'assert';
import { ObjectId } from 'mongodb';
import { command, container } from '../src';
import Services from '../src/DIContainer/Services';
import Mongo, { CollectionEnum } from '../src/storage/mongo/Mongo';
import TimeModule from '../src/TimeModule';
import {
    createFixtureData,
    generateEventsForSplitImport1,
    generateEventsForSplitImport2,
    generateExpectedUsageStats,
} from './dataProvider';

describe('generate usageStats', () => {
    beforeEach(async () => {
        const mongo = container.get<Mongo>(Services.MONGO);
        await mongo.dropCollections();
    });

    it('ok', async () => {
        const mongo = container.get<Mongo>(Services.MONGO);

        await createFixtureData();
        await command();

        const usageStatsMonthlyAppInstall = await mongo.getUsageStatsCollection(CollectionEnum.USAGE_STATS_MONTHLY)
            .find({ type: { $in: ['enduser_app_install', 'enduser_app_uninstall', 'min_price_diff'] } }).sort({ start: 1, _id: 1 }).toArray();

        const usageStatsMonthlyCloudInstall = await mongo.getUsageStatsCollection(CollectionEnum.USAGE_STATS_MONTHLY)
            .find({ type: { $in: ['cloud_install', 'cloud_uninstall'] } }).toArray();

        const usageStatsMonthlyOrchestyOperations = await mongo.getUsageStatsCollection(
            CollectionEnum.USAGE_STATS_MONTHLY,
        ).find({ type: { $in: ['orchesty_operations'] } }).sort({ start: 1 }).toArray();

        const metadata = await mongo.getUsageStatsCollection(CollectionEnum.USAGE_STATS_METADATA)
            .find().toArray();

        assert.deepEqual(usageStatsMonthlyAppInstall.map((us) => {
            delete (us as { _id?: ObjectId })._id;
            us.start = us.start.toISOString();
            us.end = us.end.toISOString();
            return us;
        }), generateExpectedUsageStats());

        assert.deepEqual(usageStatsMonthlyOrchestyOperations.map((us) => {
            delete (us as { _id?: ObjectId })._id;
            us.start = us.start.toISOString();
            us.end = us.end.toISOString();
            return us;
        }), [{
            cost: 24,
            end: '2023-02-01T00:00:00.000Z',
            instanceId: 'iid1',
            start: '2023-01-01T00:00:00.000Z',
            tenantId: 't1',
            type: 'orchesty_operations',
        }, {
            cost: 26,
            end: '2023-04-01T00:00:00.000Z',
            instanceId: 'iid1',
            start: '2023-03-01T00:00:00.000Z',
            tenantId: 't1',
            type: 'orchesty_operations',
        }]);

        assert.deepEqual(usageStatsMonthlyCloudInstall.map((us) => {
            delete (us as { _id?: ObjectId })._id;
            us.start = us.start.toISOString();
            us.end = us.end.toISOString();
            return us;
        }), [{
            cost: 900000,
            end: '2023-01-03T03:00:00.000Z',
            installed: false,
            instanceId: 'iid1',
            start: '2023-01-01T03:00:00.000Z',
            tenantId: 't1',
            type: 'cloud_install',
        }, {
            cost: 900000,
            end: '2023-04-01T00:00:00.000Z',
            installed: false,
            instanceId: 'iid1',
            start: '2023-03-05T01:00:00.000Z',
            tenantId: 't1',
            type: 'cloud_install',
        }, {
            cost: 900000,
            end: '2023-04-05T00:00:00.000Z',
            installed: true,
            instanceId: 'iid1',
            start: '2023-04-01T00:00:00.000Z',
            tenantId: 't1',
            type: 'cloud_install',
        }]);

        assert.deepEqual(metadata.map((m) => {
            delete (m as { _id?: ObjectId })._id;

            Object.keys(m.instances).forEach((key) => {
                m.instances[key] = {
                    billingHistoryEnd: m.instances[key].billingHistoryEnd.toISOString(),
                    lastRunHighestEventDate: m.instances[key].lastRunHighestEventDate.toISOString(),
                };
            });

            return m;
        }), [{
            instances: {
                iid1: {
                    billingHistoryEnd: '2023-04-05T00:00:00.000Z',
                    lastRunHighestEventDate: '2023-03-15T01:00:00.000Z',
                },
                iid2: {
                    billingHistoryEnd: '2023-04-05T00:00:00.000Z',
                    lastRunHighestEventDate: '2023-03-15T01:00:00.000Z',
                },
            }, tenantId: 't1',
        }, {
            instances: {
                iid3: {
                    billingHistoryEnd: '2023-04-05T00:00:00.000Z',
                    lastRunHighestEventDate: '2023-03-15T01:00:00.000Z',
                },
            }, tenantId: 't2',
        }]);
    });

    it('missing module', async () => {
        const mongo = container.get<Mongo>(Services.MONGO);

        await createFixtureData();
        await mongo.getBillingAdminCollection(CollectionEnum.MODULE).deleteOne({ appName: 'woocommerce' });

        try {
            await command();
        } catch (e) {
            assert.deepEqual((e as Error).message, 'Module not found! applinthId=[a1a1a1a1a1a1a1a1a1a1a1a1], appName=[woocommerce]');
        }
    });

    it('split run', async () => {
        await createFixtureData(false);
        await generateEventsForSplitImport1();
        await command();

        jest.spyOn(TimeModule.prototype, 'getNow').mockImplementation(() => 1693550742000);

        const endOfMonthDay = new Date(1693550742000);
        endOfMonthDay.setMonth(endOfMonthDay.getMonth() + 1);
        endOfMonthDay.setDate(1);
        endOfMonthDay.setHours(0, 0, 0, 0);

        jest.spyOn(TimeModule.prototype, 'getEndOfMonthDay').mockImplementation(() => endOfMonthDay);

        await generateEventsForSplitImport2();

        await command();

        const mongo = container.get<Mongo>(Services.MONGO);

        const usageStatsMonthlyAppInstall = await mongo.getUsageStatsCollection(CollectionEnum.USAGE_STATS_MONTHLY)
            .find({ type: { $in: ['enduser_app_install', 'enduser_app_uninstall', 'min_price_diff'] } }).toArray();

        const usageStatsMonthlyCloudInstall = await mongo.getUsageStatsCollection(CollectionEnum.USAGE_STATS_MONTHLY)
            .find({ type: { $in: ['cloud_install', 'cloud_uninstall'] } }).toArray();

        const usageStatsMonthlyOrchestyOperations = await mongo.getUsageStatsCollection(
            CollectionEnum.USAGE_STATS_MONTHLY,
        ).find({ type: { $in: ['orchesty_operations'] } }).sort({ start: 1 }).toArray();

        assert.deepEqual(usageStatsMonthlyAppInstall.map((us) => {
            delete (us as { _id?: ObjectId })._id;
            us.start = us.start.toISOString();
            us.end = us.end.toISOString();
            return us;
        }), [
            {
                appId: 'shoptet',
                start: '2023-01-01T08:52:15.000Z',
                end: '2023-01-05T08:52:15.000Z',
                endUserId: '1',
                type: 'enduser_app_install',
                installId: 'AID1672563135000',
                instanceId: 'iid1',
                tenantId: 't1',
                installed: false,
                cost: 13,
            },
            {
                appId: 'shoptet',
                start: '2023-01-15T08:52:15.000Z',
                end: '2023-02-01T00:00:00.000Z',
                endUserId: '1',
                type: 'enduser_app_install',
                installId: 'AID1673772735000',
                instanceId: 'iid1',
                tenantId: 't1',
                installed: false,
                cost: 55,
            },
            {
                appId: 'shoptet',
                start: '2023-02-01T00:00:00.000Z',
                end: '2023-02-15T08:52:15.000Z',
                endUserId: '1',
                type: 'enduser_app_install',
                installId: 'AID1673772735000',
                instanceId: 'iid1',
                tenantId: 't1',
                installed: false,
                cost: 54,
            },
            {
                appId: 'shoptet',
                start: '2023-02-17T08:52:15.000Z',
                end: '2023-02-21T08:52:15.000Z',
                endUserId: '1',
                type: 'enduser_app_install',
                installId: 'AID1676623935000',
                instanceId: 'iid1',
                tenantId: 't1',
                installed: false,
                cost: 14,
            },
            {
                appId: 'shoptet',
                start: '2023-02-25T08:52:15.000Z',
                end: '2023-03-01T00:00:00.000Z',
                endUserId: '1',
                type: 'enduser_app_install',
                installId: 'AID1677315135000',
                instanceId: 'iid1',
                tenantId: 't1',
                installed: false,
                cost: 14,
            },
            {
                appId: 'shoptet',
                start: '2023-03-01T00:00:00.000Z',
                end: '2023-04-01T00:00:00.000Z',
                endUserId: '1',
                type: 'enduser_app_install',
                installId: 'AID1677315135000',
                instanceId: 'iid1',
                tenantId: 't1',
                installed: false,
                cost: 100,
            },
            {
                appId: 'shoptet',
                start: '2023-06-01T00:00:00.000Z',
                end: '2023-06-05T06:45:42.000Z',
                endUserId: '1',
                type: 'enduser_app_install',
                installId: 'AID1677315135000',
                instanceId: 'iid1',
                tenantId: 't1',
                installed: false,
                cost: 17,
            },
            {
                appId: 'shoptet',
                start: '2023-04-01T00:00:00.000Z',
                end: '2023-05-01T00:00:00.000Z',
                endUserId: '1',
                type: 'enduser_app_install',
                installId: 'AID1677315135000',
                instanceId: 'iid1',
                tenantId: 't1',
                installed: false,
                cost: 100,
            },
            {
                appId: 'shoptet',
                start: '2023-05-01T00:00:00.000Z',
                end: '2023-06-01T00:00:00.000Z',
                endUserId: '1',
                type: 'enduser_app_install',
                installId: 'AID1677315135000',
                instanceId: 'iid1',
                tenantId: 't1',
                installed: false,
                cost: 100,
            },
            {
                appId: 'shoptet',
                start: '2023-07-02T06:45:42.000Z',
                end: '2023-07-20T06:45:42.000Z',
                endUserId: '1',
                type: 'enduser_app_install',
                installId: 'AID1688280342000',
                instanceId: 'iid1',
                tenantId: 't1',
                installed: false,
                cost: 58,
            },
            {
                appId: 'shoptet',
                start: '2023-07-25T06:45:42.000Z',
                end: '2023-07-27T06:45:42.000Z',
                endUserId: '1',
                type: 'enduser_app_install',
                installId: 'AID1690267542000',
                instanceId: 'iid1',
                tenantId: 't1',
                installed: false,
                cost: 6,
            },
            {
                appId: 'shoptet',
                start: '2023-07-29T06:45:42.000Z',
                end: '2023-08-01T00:00:00.000Z',
                endUserId: '1',
                type: 'enduser_app_install',
                installId: 'AID1690613142000',
                instanceId: 'iid1',
                tenantId: 't1',
                installed: false,
                cost: 10,
            },
            {
                appId: 'shoptet',
                start: '2023-08-01T00:00:00.000Z',
                end: '2023-09-01T00:00:00.000Z',
                endUserId: '1',
                type: 'enduser_app_install',
                installId: 'AID1690613142000',
                instanceId: 'iid1',
                tenantId: 't1',
                installed: false,
                cost: 100,
            },
            {
                appId: 'shoptet',
                start: '2023-09-01T00:00:00.000Z',
                end: '2023-09-01T06:45:42.000Z',
                endUserId: '1',
                type: 'enduser_app_install',
                installId: 'AID1690613142000',
                instanceId: 'iid1',
                tenantId: 't1',
                installed: true,
                cost: 3,
                estimatedCost: 100,
            },
        ]);

        assert.deepEqual(usageStatsMonthlyOrchestyOperations.map((us) => {
            delete (us as { _id?: ObjectId })._id;
            us.start = us.start.toISOString();
            us.end = us.end.toISOString();
            return us;
        }), [{
            cost: 24,
            end: '2023-02-01T00:00:00.000Z',
            instanceId: 'iid1',
            start: '2023-01-01T00:00:00.000Z',
            tenantId: 't1',
            type: 'orchesty_operations',
        }, {
            cost: 26,
            end: '2023-04-01T00:00:00.000Z',
            instanceId: 'iid1',
            start: '2023-03-01T00:00:00.000Z',
            tenantId: 't1',
            type: 'orchesty_operations',
        }, {
            cost: 26,
            end: '2023-09-01T00:00:00.000Z',
            instanceId: 'iid1',
            start: '2023-08-01T00:00:00.000Z',
            tenantId: 't1',
            type: 'orchesty_operations',
        }]);

        assert.deepEqual(usageStatsMonthlyCloudInstall.map((us) => {
            delete (us as { _id?: ObjectId })._id;
            us.start = us.start.toISOString();
            us.end = us.end.toISOString();
            return us;
        }), [{
            cost: 900000,
            end: '2023-01-03T03:00:00.000Z',
            installed: false,
            instanceId: 'iid1',
            start: '2023-01-01T03:00:00.000Z',
            tenantId: 't1',
            type: 'cloud_install',
        }, {
            cost: 900000,
            end: '2023-04-01T00:00:00.000Z',
            installed: false,
            instanceId: 'iid1',
            start: '2023-03-05T01:00:00.000Z',
            tenantId: 't1',
            type: 'cloud_install',
        }, {
            cost: 900000,
            end: '2023-08-01T08:52:15.000Z',
            installed: false,
            instanceId: 'iid1',
            start: '2023-08-01T00:00:00.000Z',
            tenantId: 't1',
            type: 'cloud_install',
        }, {
            cost: 900000,
            end: '2023-05-01T00:00:00.000Z',
            installed: false,
            instanceId: 'iid1',
            start: '2023-04-01T00:00:00.000Z',
            tenantId: 't1',
            type: 'cloud_install',
        }, {
            cost: 900000,
            end: '2023-06-01T00:00:00.000Z',
            installed: false,
            instanceId: 'iid1',
            start: '2023-05-01T00:00:00.000Z',
            tenantId: 't1',
            type: 'cloud_install',
        }, {
            cost: 900000,
            end: '2023-07-01T00:00:00.000Z',
            installed: false,
            instanceId: 'iid1',
            start: '2023-06-01T00:00:00.000Z',
            tenantId: 't1',
            type: 'cloud_install',
        }, {
            cost: 900000,
            end: '2023-08-01T00:00:00.000Z',
            installed: false,
            instanceId: 'iid1',
            start: '2023-07-01T00:00:00.000Z',
            tenantId: 't1',
            type: 'cloud_install',
        }]);
    });
});
