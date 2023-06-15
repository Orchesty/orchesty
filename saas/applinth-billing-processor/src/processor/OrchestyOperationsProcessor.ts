import { Collection, ObjectId } from 'mongodb';
import Services from '../DIContainer/Services';
import { UsageStatsType } from '../enums/UsageStatsType';
import { container } from '../index';
import Mongo, { CollectionEnum } from '../storage/mongo/Mongo';
import TimeModule from '../TimeModule';
import IProcessor from './IProcessor';

export class OrchestyOperationsProcessor implements IProcessor {

    private readonly timeModule: TimeModule;

    public constructor() {
        this.timeModule = container.get<TimeModule>(Services.TIME_MODULE);
    }

    public async process(metadataRecord: Record<string, unknown>): Promise<Record<string, unknown>> {
        const mongo = container.get<Mongo>(Services.MONGO);

        const colMetadata = mongo.getBillingCollection(CollectionEnum.USAGE_STATS_METADATA);
        const colMonthly = mongo.getBillingCollection(CollectionEnum.USAGE_STATS_MONTHLY);
        const orchestras = await mongo.getBillingAdminCollection(CollectionEnum.ORCHESTY).find().toArray();

        await this.generate(orchestras as IOrchesty[], mongo, colMetadata, colMonthly);

        return metadataRecord;
    }

    private async generate(
        orchestras: IOrchesty[],
        mongo: Mongo,
        colMetadata: Collection,
        colMonthly: Collection,
    ): Promise<void> {
        for (const orchesty of orchestras) {
            const metadata = (await colMetadata.findOne(
                { tenantId: orchesty.tenantId },
            ))?.instances[orchesty.instanceId];

            const lastHighestDate = metadata ? metadata.lastRunHighestEventDate : null;
            const coll = mongo.getUsageStatsCollection(CollectionEnum.EVENTS);

            await this.generateMonthlyStats(lastHighestDate, orchesty, coll, colMonthly);
        }
    }

    private async generateMonthlyStats(
        lastHighestDate: Date | null,
        orchesty: IOrchesty,
        eventCollection: Collection,
        colMonthly: Collection,
    ): Promise<void> {
        const createdFilter: { $gt?: string; $lte?: string } = {};
        if (lastHighestDate) {
            createdFilter.$gt = String(lastHighestDate.getTime());
        }
        if (this.timeModule.getNow()) {
            createdFilter.$lte = String(this.timeModule.getNow() * 1000);
        }

        const res = await eventCollection.aggregate([
            {
                $match: {
                    type: UsageStatsType.ORCHESTY_OPERATIONS,
                    created: createdFilter,
                },
            },
            {
                $group: {
                    _id: {
                        year: {
                            $year: {
                                $convert: {
                                    input: {
                                        $toLong: {
                                            $substr: [
                                                '$created',
                                                0,
                                                13,
                                            ],
                                        },
                                    },
                                    to: 'date',
                                },
                            },
                        },
                        month: {
                            $month: {
                                $convert: {
                                    input: {
                                        $toLong: {
                                            $substr: [
                                                '$created',
                                                0,
                                                13,
                                            ],
                                        },
                                    },
                                    to: 'date',
                                },
                            },
                        },
                    },
                    total: { $sum: '$data.total' },
                },
            },
            {
                $sort: { year: 1, month: 1 },
            },
        ]).toArray();

        for (const item of res) {
            const firstDay = new Date(item._id.year, item._id.month - 1, 1);
            const lastDay = new Date(firstDay.getUTCFullYear(), firstDay.getMonth() + 1, 1);
            const filter = {
                instanceId: orchesty.instanceId,
                tenantId: orchesty.tenantId,
                type: UsageStatsType.ORCHESTY_OPERATIONS,
                start: firstDay,
                end: lastDay,
            };
            await colMonthly.updateOne(filter, {
                $set: {
                    ...filter,
                    cost: item.total * orchesty.price,
                },
            }, { upsert: true });
        }
    }

}

export interface IOrchesty {
    _id: ObjectId;
    tenantId: string;
    instanceId: string;
    version: number;
    price: number;
    startDate: Date;
}
