import assert from 'assert';
import { ObjectId } from 'mongodb';
import { command, container } from '../src';
import Services from '../src/DIContainer/Services';
import Mongo, { CollectionEnum } from '../src/storage/mongo/Mongo';
import { createFixtureData, generateExpectedUsageStats } from './dataProvider';

describe('generate usageStats', () => {
    it('ok', async () => {
        const mongo = container.get<Mongo>(Services.MONGO);

        await createFixtureData();
        await command();

        const usageStatsMonthly = await mongo.getUsageStatsCollection(CollectionEnum.USAGE_STATS_MONTHLY)
            .find().toArray();

        const metadata = await mongo.getUsageStatsCollection(CollectionEnum.USAGE_STATS_METADATA)
            .find().toArray();

        assert.deepEqual(usageStatsMonthly.map((us) => {
            delete (us as { _id?: ObjectId })._id;
            us.start = us.start.toISOString();
            us.end = us.end.toISOString();
            return us;
        }), generateExpectedUsageStats());

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
});
