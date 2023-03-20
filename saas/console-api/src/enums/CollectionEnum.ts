import GranularityError from '../errors/GranularityError';
import { GranularityEnum } from './GranularityEnum';

export enum CollectionEnum {
    USAGE_STATS_MONTHLY = 'usage_stats_monthly',
    USAGE_STATS_DAILY = 'usage_stats_daily',
    USAGE_STATS_HOURLY = 'usage_stats_hourly',
    TENANT = 'tenant',
    CLIENT = 'client',
    USAGE_STATS_METADATA = 'usage_stats_metadata',
}

export function switchGranularity(_shortName?: string): CollectionEnum {
    let shortName = _shortName;
    if (!shortName) {
        shortName = GranularityEnum.MONTHLY;
    }

    switch (shortName) {
        case GranularityEnum.MONTHLY:
            return CollectionEnum.USAGE_STATS_MONTHLY;
        case GranularityEnum.DAILY:
            return CollectionEnum.USAGE_STATS_DAILY;
        case GranularityEnum.HOURLY:
            return CollectionEnum.USAGE_STATS_HOURLY;
        default:
            throw new GranularityError();
    }
}
