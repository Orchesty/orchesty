import { DateTime } from 'luxon';
import Mongo from '../storage/mongo/Mongo';
import { IAppsAggregationParams } from '../controllers/usageStats';
import DateParseError from '../errors/DateParseError';
import { CollectionEnum, switchGranularity } from '../enums/CollectionEnum';

interface IMongoQuery {
  tenantId?: string,
  start?: object,
  end?: object | string,
  endUserId?: string,
  endUserDisplayId?: RegExp,
  instanceId?: string,
  appName?: string,
}

export default class UsageStatsService {
  constructor(private _db: Mongo) {
  }

  public async getDataForAppsAggregation(
    query: IAppsAggregationParams,
    tenantId: string,
  ) {
    const mongoQuery = this._prepareMongoQuery(query, tenantId);
    const collectionName = switchGranularity(query.granularity);

    const aggregations = [
      {
        $match: mongoQuery,
      },
      {
        $group: {
          // eslint-disable-next-line @typescript-eslint/naming-convention
          _id: '$appName',
          instanceIds: { $addToSet: '$instanceId' },
          userIds: { $addToSet: '$endUserId' },
          totalCost: { $sum: '$cost' },
          installCount: { $push: '$_id' },
        },
      },
      {
        // eslint-disable-next-line @typescript-eslint/naming-convention
        $sort: { _id: 1 },
      },
      {
        $project: {
          // eslint-disable-next-line @typescript-eslint/naming-convention
          _id: 0,
          appName: '$_id',
          endUsers: { $size: '$userIds' },
          totalCost: 1,
          instanceIds: 1,
          installCount: { $size: '$installCount' },
        },
      },
    ];

    const rows = await this._db.getCollection(collectionName).aggregate(aggregations).toArray();
    rows.forEach((row) => {
      row.instanceIds.sort();
    });
    return { rows };
  }

  public async getDataForInstalledAppsAggregation(
    query: IAppsAggregationParams,
    tenantId: string,
  ) {
    const collectionName = CollectionEnum.USAGE_STATS_MONTHLY;
    const mongoQuery = this._prepareMongoQuery(query, tenantId, true);

    const apps = await this._db.getCollection(collectionName).find(mongoQuery).toArray();

    const installIds = apps.map((element) => element.installId);

    const aggregations = [
      {
        $match: { installId: { $in: installIds } },
      },
      {
        $group: {
          // eslint-disable-next-line @typescript-eslint/naming-convention
          _id: { appName: '$appName', instanceId: '$instanceId' },
          installed: { $min: '$start' },
          appName: { $first: '$appName' },
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
          appName: 1,
          installed: 1,
          instanceId: 1,
        },
      },
    ];

    const rows = await this._db.getCollection(collectionName).aggregate(aggregations).toArray();
    return { rows };
  }

  public async getDataForTimeBucketAppsAggregation(
    query: IAppsAggregationParams,
    tenantId: string,
  ) {
    const collectionName = CollectionEnum.USAGE_STATS_MONTHLY;
    const mongoQuery = this._prepareMongoQuery(query, tenantId);

    const aggregations = [
      {
        $match: mongoQuery,
      },
      {
        $group: {
          // eslint-disable-next-line @typescript-eslint/naming-convention
          _id: { $dateToString: { format: '%m/%Y', date: '$start' } },
          date: { $first: '$start' },
          totalCost: { $sum: '$cost' },
          appNames: { $addToSet: '$appName' },
          instanceIds: { $addToSet: '$instanceId' },
        },
      },
      {
        $sort: { date: 1 },
      },
      {
        $project: {
          // eslint-disable-next-line @typescript-eslint/naming-convention
          _id: 0,
          appNames: 1,
          instanceIds: 1,
          formattedDate: this._getFormattedDate(),
          totalCost: 1,
        },
      },
    ];

    const rows = await this._db.getCollection(collectionName).aggregate(aggregations).toArray();
    rows.forEach((row) => {
      row.appNames.sort();
      row.instanceIds.sort();
    });
    return { rows };
  }

  public async getDataForTimeBucketUsersAggregation(
    query: IAppsAggregationParams,
    tenantId: string,
  ) {
    const collectionName = CollectionEnum.USAGE_STATS_MONTHLY;
    const mongoQuery = this._prepareMongoQuery(query, tenantId);

    const aggregations = [
      {
        $match: mongoQuery,
      },
      {
        $group: {
          // eslint-disable-next-line @typescript-eslint/naming-convention
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
          // eslint-disable-next-line @typescript-eslint/naming-convention
          _id: 0,
          endUsers: { $size: '$userIds' },
          formattedDate: this._getFormattedDate(),
        },
      },
    ];

    const rows = await this._db.getCollection(collectionName).aggregate(aggregations).toArray();
    return { rows };
  }

  public async getDataForUsersAggregation(
    query: IAppsAggregationParams,
    tenantId: string,
  ) {
    const mongoQuery = this._prepareMongoQuery(query, tenantId);
    const collectionName = switchGranularity(query.granularity);

    const aggregations = [
      {
        $match: mongoQuery,
      },
      {
        $group: {
          // eslint-disable-next-line @typescript-eslint/naming-convention
          _id: '$endUserDisplayId',
          endUserId: { $first: '$endUserId' },
          appNames: { $addToSet: '$appName' },
          instanceIds: { $addToSet: '$instanceId' },
          totalCost: { $sum: '$cost' },
          installCount: { $push: '$_id' },
        },
      },
      {
        $project: {
          // eslint-disable-next-line @typescript-eslint/naming-convention
          _id: 0,
          endUserDisplayId: '$_id',
          endUserId: 1,
          appNames: 1,
          instanceIds: 1,
          totalCost: 1,
          installCount: { $size: '$installCount' },
        },
      },
      {
        $sort: { endUserDisplayId: 1 },
      },

    ];

    const rows = await this._db.getCollection(collectionName).aggregate(aggregations).toArray();
    rows.forEach((row) => {
      row.appNames.sort();
      row.instanceIds.sort();
    });
    return { rows };
  }

  private _getFormattedDate = () => ({
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
  });

  private _prepareMongoQuery = (query: IAppsAggregationParams, tenantId: string, requireInstalledDate = false) => {
    const mongoQuery = {} as IMongoQuery;
    if (query.tenantId) {
      mongoQuery.tenantId = query.tenantId;
    } else {
      mongoQuery.tenantId = tenantId;
    }

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
    } catch (e) {
      throw new DateParseError('Parameter timeRangeStart and/or timeRangeEnd is/are in invalid format!', 1);
    }

    try {
      if (requireInstalledDate) {
        const installedDate = query.installedDate ? DateTime.fromISO(query.installedDate) : DateTime.now().toISODate();
        mongoQuery.end = {
          $gte: installedDate,
        };
      }
    } catch (e) {
      throw new DateParseError('Parameter installedDated is in invalid format!', 2);
    }

    if (query.endUserId) {
      mongoQuery.endUserId = query.endUserId;
    }

    if (query.endUserDisplayId) {
      mongoQuery.endUserDisplayId = new RegExp(query.endUserDisplayId, 'i');
    }

    if (query.instanceId) {
      mongoQuery.instanceId = query.instanceId;
    }

    if (query.appName) {
      mongoQuery.appName = query.appName;
    }

    return mongoQuery;
  };
}
