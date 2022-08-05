import { Request, Response } from 'express';
import { usageStatsService } from '../index';
import { getLoggedUser, getLoggedUserPermissions, hasPermission } from '../security/securityService';
import handleError from '../handlers/errorHandler';
import { ResourceEnum } from '../enums/ResourceEnum';
import PermissionsError from '../errors/PermissionsError';

export interface IAppsAggregationParams {
  tenantId?: string,
  timeRangeStart: string,
  timeRangeEnd: string,
  endUserId?: string,
  endUserDisplayId?: string,
  appName?: string,
  installedDate?: string,
  granularity?: string,
}

function preprocessRequest(req: Request): {query: IAppsAggregationParams, tenantId: string} {
  const query = req.query as unknown as IAppsAggregationParams;
  const tenantId = getLoggedUser(req);
  let allowed = true;

  if (query.tenantId && query.tenantId !== tenantId) {
    allowed = hasPermission(getLoggedUserPermissions(req), ResourceEnum.GET_USAGE_STATS_FROM_ANOTHER_TENANT);
  }

  if (!allowed) {
    throw new PermissionsError();
  }
  return { query, tenantId };
}

export const usageStatsApps = async (req: Request, res: Response) => {
  try {
    const { query, tenantId } = preprocessRequest(req);
    const result = await usageStatsService.getDataForAppsAggregation(query, tenantId);

    res.status(200).send(result);
  } catch (e) {
    handleError(e as Error, req, res);
  }
};

export const usageStatsInstalledApps = async (req: Request, res: Response) => {
  try {
    const { query, tenantId } = preprocessRequest(req);
    const result = await usageStatsService.getDataForInstalledAppsAggregation(query, tenantId);

    res.status(200).send(result);
  } catch (e) {
    handleError(e as Error, req, res);
  }
};

export const usageStatsTimeBucketApps = async (req: Request, res: Response) => {
  try {
    const { query, tenantId } = preprocessRequest(req);
    const result = await usageStatsService.getDataForTimeBucketAppsAggregation(query, tenantId);

    res.status(200).send(result);
  } catch (e) {
    handleError(e as Error, req, res);
  }
};

export const usageStatsTimeBucketUsers = async (req: Request, res: Response) => {
  try {
    const { query, tenantId } = preprocessRequest(req);
    const result = await usageStatsService.getDataForTimeBucketUsersAggregation(query, tenantId);

    res.status(200).send(result);
  } catch (e) {
    handleError(e as Error, req, res);
  }
};

export const usageStatsUsers = async (req: Request, res: Response) => {
  try {
    const { query, tenantId } = preprocessRequest(req);
    const result = await usageStatsService.getDataForUsersAggregation(query, tenantId);

    res.status(200).send(result);
  } catch (e) {
    handleError(e as Error, req, res);
  }
};
