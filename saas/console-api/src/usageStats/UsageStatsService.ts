import { DateTime } from 'luxon';
import { IAppsAggregationParams } from '../controllers/usageStats';
import { CollectionEnum, switchGranularity } from '../enums/CollectionEnum';
import DateParseError from '../errors/DateParseError';
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

export default class UsageStatsService {

    public constructor(private readonly db: BillingMongo) {
    }

    public async getDataForAppsAggregation(
        query: IAppsAggregationParams,
        tenantId: string,
    ): Promise<{ rows: unknown }> {
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
                    installCount: { $push: '$_id' },
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
        return { rows };
    }

    public async getDataForInstalledAppsAggregation(
        query: IAppsAggregationParams,
        tenantId: string,
    ): Promise<{ rows: unknown }> {
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
        return { rows };
    }

    public async getDataForTimeBucketAppsAggregation(
        query: IAppsAggregationParams,
        tenantId: string,
    ): Promise<{ rows: unknown }> {
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
                    timeBucketName: this.getFormattedDate(),
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
        return { rows };
    }

    public async getDataForTimeBucketUsersAggregation(
        query: IAppsAggregationParams,
        tenantId: string,
    ): Promise<{ rows: unknown }> {
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
                    timeBucketName: this.getFormattedDate(),
                },
            },
        ];

        const rows = await this.db.getBillingCollection(collectionName).aggregate(aggregations).toArray();
        return { rows };
    }

    public async getDataForUsersAggregation(
        query: IAppsAggregationParams,
        tenantId: string,
    ): Promise<{ rows: unknown }> {
        const mongoQuery = this.prepareMongoQuery(query, tenantId, false, true);
        const collectionName = switchGranularity(query.granularity);

        const aggregations = [
            {
                $match: mongoQuery,
            },
            {
                $group: {
                    _id: '$endUserId',
                    endUserId: { $first: '$endUserId' },
                    appIds: { $addToSet: '$appId' },
                    appNames: { $addToSet: '$appId' },
                    activeAppNames: { $addToSet: { $cond: { if: { $eq: ['$installed', true] }, then: '$appId', else: '$$REMOVE' } } },
                    instanceIds: { $addToSet: '$instanceId' },
                    totalCost: { $sum: '$cost' },
                    estimatedTotalCost: { $sum: '$estimatedCost' },
                    installCount: { $push: '$_id' },
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
        return { rows };
    }

    private getFormattedDate(): unknown {
        return {
            $let: {
                vars: {
                    parts: { $split: ['$_id', '/'] },
                },
                in: {
                    $concat: [
                        { $arrayElemAt: ['$$parts', 0] }, '/',
                        { $substr: [{ $arrayElemAt: ['$$parts', 1] }, 2, 2] },
                    ],
                },
            },
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
                        $lt: endDate,
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
                            $lt: DateTime.local().endOf('month'),
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

}
