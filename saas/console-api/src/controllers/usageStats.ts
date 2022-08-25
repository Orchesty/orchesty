import { Request, Response } from 'express';
import { ResourceEnum } from '../enums/ResourceEnum';
import PermissionsError from '../errors/PermissionsError';
import handleError from '../handlers/errorHandler';
import { usageStatsService } from '../index';
import { getLoggedUser, getLoggedUserPermissions, hasPermission } from '../security/securityService';

export interface IAppsAggregationParams {
    tenantId?: string;
    timeRangeStart: string;
    timeRangeEnd: string;
    endUserId?: string;
    endUserDisplayId?: string;
    instanceId?: string;
    appName?: string;
    installedDate?: string;
    granularity?: string;
}

function preprocessRequest(req: Request): { query: IAppsAggregationParams; tenantId: string } {
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

export async function usageStatsApps(req: Request, res: Response): Promise<void> {
    try {
        const { query, tenantId } = preprocessRequest(req);
        const result = await usageStatsService.getDataForAppsAggregation(query, tenantId);

        res.status(200).send(result);
    } catch (e) {
        handleError(e as Error, req, res);
    }
}

export async function usageStatsInstalledApps(req: Request, res: Response): Promise<void> {
    try {
        const { query, tenantId } = preprocessRequest(req);
        const result = await usageStatsService.getDataForInstalledAppsAggregation(query, tenantId);

        res.status(200).send(result);
    } catch (e) {
        handleError(e as Error, req, res);
    }
}

export async function usageStatsTimeBucketApps(req: Request, res: Response): Promise<void> {
    try {
        const { query, tenantId } = preprocessRequest(req);
        const result = await usageStatsService.getDataForTimeBucketAppsAggregation(query, tenantId);

        res.status(200).send(result);
    } catch (e) {
        handleError(e as Error, req, res);
    }
}

export async function usageStatsTimeBucketUsers(req: Request, res: Response): Promise<void> {
    try {
        const { query, tenantId } = preprocessRequest(req);
        const result = await usageStatsService.getDataForTimeBucketUsersAggregation(query, tenantId);

        res.status(200).send(result);
    } catch (e) {
        handleError(e as Error, req, res);
    }
}

export async function usageStatsUsers(req: Request, res: Response): Promise<void> {
    try {
        const { query, tenantId } = preprocessRequest(req);
        const result = await usageStatsService.getDataForUsersAggregation(query, tenantId);

        res.status(200).send(result);
    } catch (e) {
        handleError(e as Error, req, res);
    }
}
