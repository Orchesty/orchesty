import { GranularityEnum } from './GranularityEnum';
import GranularityError from '../errors/GranularityError';

export enum CollectionEnum {
  USAGE_STATS_MONTHLY = 'usage_stats_monthly',
  USAGE_STATS_DAILY = 'usage_stats_daily',
  USAGE_STATS_HOURLY = 'usage_stats_hourly',
}

export function switchGranularity(_shortName?: string) {
  let shortName = _shortName;
  if (!shortName) {
    shortName = GranularityEnum.HOURLY;
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
