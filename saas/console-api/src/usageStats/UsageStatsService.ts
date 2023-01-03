import { DateTime } from 'luxon';
import { IAppsAggregationParams } from '../controllers/usageStats';
import { CollectionEnum, switchGranularity } from '../enums/CollectionEnum';
import DateParseError from '../errors/DateParseError';
import GranularityError from '../errors/GranularityError';
import MetadataSearchError from '../errors/MetadataSearchError';
import BillingMongo from '../storage/mongo/Mongo';

interface IMongoQuery {
    tenantId?: string;
    start?: object;
    end?: object | string;
    endUserId?: RegExp | string;
    instanceId?: string;
    appId?: string;
    installed?: boolean;
}

interface IMetadata {
    billingHistoryStart: Date;
    billingHistoryEnd: Date;
}

interface IDbMetadata {
    tenantId: string;
    instances: Record<string, IMetadata>;
}

interface IMetadataResponse {
    billingHistoryStart: string;
    billingHistoryEnd: string;
}

export default class UsageStatsService {

    public constructor(private readonly db: BillingMongo) {
    }

    public async getDataForAppsAggregation(
        query: IAppsAggregationParams,
        tenantId: string,
    ): Promise<IMetadataResponse & { rows: unknown }> {
        const mongoQuery = this.prepareMongoQuery(query, tenantId, false, true);
        const collectionName = switchGranularity(query.granularity);

        const aggregations = [
            {
                $match: mongoQuery,
            },
            {
                $group: {
                    _id: '$appId',
                    appName: { $first: '$appId' },
                    instanceIds: { $addToSet: '$instanceId' },
                    userIds: { $addToSet: '$endUserId' },
                    totalCost: { $sum: '$cost' },
                    estimatedTotalCost: { $sum: '$estimatedCost' },
                    installCount: { $addToSet: '$installId' },
                },
            },
            {
                $sort: { _id: 1 },
            },
            {
                $project: {
                    _id: 0,
                    appId: '$_id',
                    appName: 1,
                    endUsers: { $size: '$userIds' },
                    totalCost: 1,
                    estimatedTotalCost: 1,
                    instanceIds: 1,
                    installCount: { $size: '$installCount' },
                },
            },
        ];

        const rows = await this.db.getBillingCollection(collectionName).aggregate(aggregations).toArray();
        rows.forEach((row) => {
            // eslint-disable-next-line @typescript-eslint/no-unsafe-call
            row.instanceIds.sort();
        });
        const metadata = await this.findMetadata(tenantId, query.instanceId);
        return {
            rows,
            billingHistoryStart: metadata.billingHistoryStart.toISOString(),
            billingHistoryEnd: metadata.billingHistoryEnd.toISOString(),
        };
    }

    public async getDataForInstalledAppsAggregation(
        query: IAppsAggregationParams,
        tenantId: string,
    ): Promise<IMetadataResponse & { rows: unknown }> {
        const collectionName = CollectionEnum.USAGE_STATS_MONTHLY;
        const mongoQuery = this.prepareMongoQuery(query, tenantId, !query.tail);

        const apps = await this.db.getBillingCollection(collectionName).find(mongoQuery).toArray();

        const installIds = apps.map((element) => element.installId);

        const aggregations = [
            {
                $match: { installId: { $in: installIds } },
            },
            {
                $group: {
                    // eslint-disable-next-line @typescript-eslint/naming-convention
                    _id: { appId: '$appId', instanceId: '$instanceId' },
                    installed: { $min: '$start' },
                    appId: { $first: '$appId' },
                    appName: { $first: '$appId' },
                    instanceId: { $first: '$instanceId' },
                },
            },
            {
                $sort: { installed: 1, instanceId: 1 },
            },
            {
                $project: {
                    // eslint-disable-next-line @typescript-eslint/naming-convention
                    _id: 0,
                    appId: 1,
                    appName: 1,
                    installed: 1,
                    instanceId: 1,
                },
            },
        ];

        const rows = await this.db.getBillingCollection(collectionName).aggregate(aggregations).toArray();
        const metadata = await this.findMetadata(tenantId, query.instanceId);
        return {
            rows,
            billingHistoryStart: metadata.billingHistoryStart.toISOString(),
            billingHistoryEnd: metadata.billingHistoryEnd.toISOString(),
        };
    }

    public async getDataForTimeBucketAppsAggregation(
        query: IAppsAggregationParams,
        tenantId: string,
    ): Promise<IMetadataResponse & { rows: unknown }> {
        const collectionName = CollectionEnum.USAGE_STATS_MONTHLY;
        const mongoQuery = this.prepareMongoQuery(query, tenantId);

        const aggregations = [
            {
                $match: mongoQuery,
            },
            {
                $group: {
                    _id: { $dateToString: { format: '%m/%Y', date: '$start' } },
                    date: { $first: '$start' },
                    totalCost: { $sum: '$cost' },
                    appIds: { $addToSet: '$appId' },
                    appNames: { $addToSet: '$appId' },
                    instanceIds: { $addToSet: '$instanceId' },
                },
            },
            {
                $sort: { date: -1 },
            },
            {
                $project: {
                    _id: 0,
                    appIds: 1,
                    appNames: 1,
                    instanceIds: 1,
                    timeBucketName: '$_id',
                    totalCost: 1,
                },
            },
        ];

        const rows = await this.db.getBillingCollection(collectionName).aggregate(aggregations).toArray();
        rows.forEach((row) => {
            /* eslint-disable @typescript-eslint/no-unsafe-call */
            row.appNames.sort();
            row.appIds.sort();
            row.instanceIds.sort();
            /* eslint-enable @typescript-eslint/no-unsafe-call */
        });
        const metadata = await this.findMetadata(tenantId, query.instanceId);
        return {
            rows,
            billingHistoryStart: metadata.billingHistoryStart.toISOString(),
            billingHistoryEnd: metadata.billingHistoryEnd.toISOString(),
        };
    }

    public async getDataForTimeBucketUsersAggregation(
        query: IAppsAggregationParams,
        tenantId: string,
    ): Promise<IMetadataResponse & { rows: unknown }> {
        const collectionName = CollectionEnum.USAGE_STATS_MONTHLY;
        const mongoQuery = this.prepareMongoQuery(query, tenantId);

        const aggregations = [
            {
                $match: mongoQuery,
            },
            {
                $group: {
                    _id: { $dateToString: { format: '%m/%Y', date: '$start' } },
                    date: { $first: '$start' },
                    userIds: { $addToSet: '$endUserId' },
                },
            },
            {
                $sort: { date: 1 },
            },
            {
                $project: {
                    _id: 0,
                    endUsers: { $size: '$userIds' },
                    timeBucketName: '$_id',
                },
            },
        ];

        const rows = await this.db.getBillingCollection(collectionName).aggregate(aggregations).toArray();
        const metadata = await this.findMetadata(tenantId, query.instanceId);
        return {
            rows,
            billingHistoryStart: metadata.billingHistoryStart.toISOString(),
            billingHistoryEnd: metadata.billingHistoryEnd.toISOString(),
        };
    }

    public async getDataForTimeBucketHistoryAggregation(
        query: IAppsAggregationParams,
        tenantId: string,
    ): Promise<IMetadataResponse & { rows: unknown }> {
        const mongoQuery = this.prepareMongoQuery(query, tenantId);
        const collectionName = switchGranularity(query.granularity);
        let format;
        if (collectionName === CollectionEnum.USAGE_STATS_MONTHLY) {
            format = '%m/%Y';
        } else if (collectionName === CollectionEnum.USAGE_STATS_DAILY) {
            format = '%d/%m/%Y';
        } else {
            throw new GranularityError('Unsupported granularity! Use daily or monthly');
        }

        const aggregations = [
            {
                $match: mongoQuery,
            },
            {
                $group: {
                    _id: { $dateToString: { format, date: '$start' } },
                    date: { $first: '$start' },
                    installCount: { $addToSet: '$installId' },
                    totalCost: { $sum: '$cost' },
                },
            },
            {
                $sort: { date: 1 },
            },
            {
                $project: {
                    _id: 0,
                    installCount: { $size: '$installCount' },
                    totalCost: 1,
                    timeBucketName: '$_id',
                },
            },
        ];

        const rows = await this.db.getBillingCollection(collectionName).aggregate(aggregations).toArray();
        const metadata = await this.findMetadata(tenantId, query.instanceId);
        return {
            rows,
            billingHistoryStart: metadata.billingHistoryStart.toISOString(),
            billingHistoryEnd: metadata.billingHistoryEnd.toISOString(),
        };
    }

    public async getDataForUsersAggregation(
        query: IAppsAggregationParams,
        tenantId: string,
    ): Promise<IMetadataResponse & { rows: unknown }> {
        const mongoQuery = this.prepareMongoQuery(query, tenantId, false, true);
        const collectionName = switchGranularity(query.granularity);

        const appIdQuery: Record<string, boolean | string> = {};
        let totalCostSumEq = {};
        let estimatedTotalCostSumEq = {};
        let installCountSumEq = {};

        if (mongoQuery.appId) {
            appIdQuery['docs.appId'] = mongoQuery.appId;
            appIdQuery['docs.installed'] = true;
            totalCostSumEq = {
                $cond: {
                    if: { $eq: ['$appId', appIdQuery['docs.appId']] },
                    then: '$cost',
                    else: '$$REMOVE',
                },
            };
            estimatedTotalCostSumEq = {
                $cond: {
                    if: { $eq: ['$appId', appIdQuery['docs.appId']] },
                    then: '$estimatedCost',
                    else: '$$REMOVE',
                },
            };
            installCountSumEq = {
                $cond: {
                    if: { $eq: ['$appId', appIdQuery['docs.appId']] },
                    then: '$_id',
                    else: '$$REMOVE',
                },
            };
        } else {
            totalCostSumEq = '$cost';
            estimatedTotalCostSumEq = '$estimatedCost';
            installCountSumEq = '$_id';
        }
        delete mongoQuery.appId;

        const aggregations = [
            {
                $match: mongoQuery,
            },
            {
                $group: {
                    _id: '$endUserId',
                    docs: { $push: '$$ROOT' },
                    activeAppNames: {
                        $addToSet: {
                            $cond: {
                                if: { $eq: ['$installed', true] },
                                then: '$appId',
                                else: '$$REMOVE',
                            },
                        },
                    },
                    totalCost: {
                        $sum: totalCostSumEq,
                    },
                    estimatedTotalCost: {
                        $sum: estimatedTotalCostSumEq,
                    },
                    installCount: {
                        $push: installCountSumEq,
                    },
                },
            },
            {
                $unwind: {
                    path: '$docs',
                },
            },
            {
                $match: appIdQuery,
            },
            {
                $group: {
                    _id: '$docs.endUserId',
                    endUserId: { $first: '$docs.endUserId' },
                    appIds: { $addToSet: '$docs.appId' },
                    totalCost: { $first: '$totalCost' },
                    estimatedTotalCost: { $first: '$estimatedTotalCost' },
                    appNames: { $addToSet: '$docs.appId' },
                    activeAppNames: { $first: '$activeAppNames' },
                    instanceIds: { $addToSet: '$docs.instanceId' },
                    installCount: { $first: '$installCount' },
                },
            },
            {
                $project: {
                    _id: 0,
                    endUserDisplayId: '$_id',
                    endUserId: 1,
                    appIds: 1,
                    appNames: 1,
                    activeAppNames: 1,
                    instanceIds: 1,
                    totalCost: 1,
                    estimatedTotalCost: 1,
                    installCount: { $size: '$installCount' },
                },
            },
            {
                $sort: { endUserDisplayId: 1 },
            },

        ];

        const rows = await this.db.getBillingCollection(collectionName).aggregate(aggregations).toArray();
        rows.forEach((row) => {
            /* eslint-disable @typescript-eslint/no-unsafe-call */
            row.appNames.sort();
            row.activeAppNames.sort();
            row.appIds.sort();
            row.instanceIds.sort();
            /* eslint-enable @typescript-eslint/no-unsafe-call */
        });
        const metadata = await this.findMetadata(tenantId, query.instanceId);
        return {
            rows,
            billingHistoryStart: metadata.billingHistoryStart.toISOString(),
            billingHistoryEnd: metadata.billingHistoryEnd.toISOString(),
        };
    }

    private prepareMongoQuery(
        query: IAppsAggregationParams,
        tenantId: string,
        requireInstalledDate = false,
        setDefaultDateIfNotSet = false,
    ): IMongoQuery {
        const mongoQuery = {} as IMongoQuery;
        if (query.tenantId) {
            mongoQuery.tenantId = query.tenantId;
        } else {
            mongoQuery.tenantId = tenantId;
        }

        if (query.tail) {
            if (query.timeRangeStart || query.timeRangeEnd || query.installedDate) {
                throw new DateParseError('Parameter installedDate, timeRangeStart and/or timeRangeEnd is/are set with tail!', 3);
            }

            mongoQuery.installed = true;
        } else {
            try {
                if (query.timeRangeStart) {
                    const startDate = DateTime.fromISO(query.timeRangeStart);
                    mongoQuery.start = {
                        $gte: startDate,
                    };
                }

                if (query.timeRangeEnd) {
                    const endDate = DateTime.fromISO(query.timeRangeEnd);
                    mongoQuery.end = {
                        $lte: endDate,
                    };
                }

                if (setDefaultDateIfNotSet) {
                    if (!mongoQuery.start) {
                        mongoQuery.start = {
                            $gte: DateTime.local().startOf('month'),
                        };
                    }
                    if (!mongoQuery.end) {
                        mongoQuery.end = {
                            $lte: DateTime.local().endOf('month').plus({ second: 1 }),
                        };
                    }
                }
            } catch (e) {
                throw new DateParseError('Parameter timeRangeStart and/or timeRangeEnd is/are in invalid format!', 1);
            }
        }

        try {
            if (requireInstalledDate) {
                const installedDate = query.installedDate
                    ? DateTime.fromISO(query.installedDate) : DateTime.now().toISODate();
                mongoQuery.end = {
                    $gte: installedDate,
                };
            }
        } catch (e) {
            throw new DateParseError('Parameter installedDated is in invalid format!', 2);
        }

        // TODO az bude v DB datech hodnota endUserDisplayId tak to rozdelit do samostatnych if
        if (query.endUserDisplayId) {
            mongoQuery.endUserId = new RegExp(query.endUserDisplayId, 'i');
        } else if (query.endUserId) {
            mongoQuery.endUserId = query.endUserId;
        }

        if (query.instanceId) {
            mongoQuery.instanceId = query.instanceId;
        }

        if (query.appId) {
            mongoQuery.appId = query.appId;
        }

        return mongoQuery;
    }

    private async findMetadata(tenantId: string, instanceId: string | undefined = undefined): Promise<IMetadata> {
        const metadata = await this.db
            .getBillingCollection(CollectionEnum.USAGE_STATS_METADATA)
            .findOne({ tenantId }) as unknown as IDbMetadata;

        if (!metadata) {
            throw new MetadataSearchError();
        }

        if (instanceId) {
            if (metadata.instances[instanceId] !== undefined) {
                return metadata.instances[instanceId];
            }

            throw new MetadataSearchError();
        }

        if (Object.keys(metadata.instances).length > 0) {
            return Object.values(metadata.instances).shift() as IMetadata;
        }

        throw new MetadataSearchError();
    }

}
