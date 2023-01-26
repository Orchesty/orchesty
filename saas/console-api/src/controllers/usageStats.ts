import { Request, Response } from 'express';
import Services from '../DIContainer/Services';
import { ResourceEnum } from '../enums/ResourceEnum';
import handleError from '../handlers/errorHandler';
import { container } from '../index';
import { preprocessRequest } from '../security/securityService';
import UsageStatsService from '../usageStats/UsageStatsService';

export interface IAppsAggregationParams {
    tenantId?: string;
    timeRangeStart?: string;
    timeRangeEnd?: string;
    endUserId?: string;
    endUserDisplayId?: string;
    instanceId?: string;
    appId?: string;
    tail?: boolean;
    installedDate?: string;
    granularity?: string;
}

function getUsageStatsService(): UsageStatsService {
    return container.get<UsageStatsService>(Services.USAGE_STATS_SERVICE);
}

export async function usageStatsApps(req: Request, res: Response): Promise<void> {
    try {
        const { query, tenantId } = await preprocessRequest<IAppsAggregationParams>(req, ResourceEnum.LIST_USAGE_STATS);
        const result = await getUsageStatsService().getDataForAppsAggregation(query, tenantId);

        res.status(200).send(result);
    } catch (e) {
        handleError(e as Error, req, res);
    }
}

export async function usageStatsInstalledApps(req: Request, res: Response): Promise<void> {
    try {
        const { query, tenantId } = await preprocessRequest<IAppsAggregationParams>(req, ResourceEnum.LIST_USAGE_STATS);
        const result = await getUsageStatsService().getDataForInstalledAppsAggregation(query, tenantId);

        res.status(200).send(result);
    } catch (e) {
        handleError(e as Error, req, res);
    }
}

export async function usageStatsTimeBucketApps(req: Request, res: Response): Promise<void> {
    try {
        const { query, tenantId } = await preprocessRequest<IAppsAggregationParams>(req, ResourceEnum.LIST_USAGE_STATS);
        const result = await getUsageStatsService().getDataForTimeBucketAppsAggregation(query, tenantId);

        res.status(200).send(result);
    } catch (e) {
        handleError(e as Error, req, res);
    }
}

export async function usageStatsTimeBucketUsers(req: Request, res: Response): Promise<void> {
    try {
        const { query, tenantId } = await preprocessRequest<IAppsAggregationParams>(req, ResourceEnum.LIST_USAGE_STATS);
        const result = await getUsageStatsService().getDataForTimeBucketUsersAggregation(query, tenantId);

        res.status(200).send(result);
    } catch (e) {
        handleError(e as Error, req, res);
    }
}

export async function usageStatsTimeBucketHistory(req: Request, res: Response): Promise<void> {
    try {
        const { query, tenantId } = await preprocessRequest<IAppsAggregationParams>(req, ResourceEnum.LIST_USAGE_STATS);
        const result = await getUsageStatsService().getDataForTimeBucketHistoryAggregation(query, tenantId);

        res.status(200).send(result);
    } catch (e) {
        handleError(e as Error, req, res);
    }
}

export async function usageStatsUsers(req: Request, res: Response): Promise<void> {
    try {
        const { query, tenantId } = await preprocessRequest<IAppsAggregationParams>(req, ResourceEnum.LIST_USAGE_STATS);
        const result = await getUsageStatsService().getDataForUsersAggregation(query, tenantId);

        res.status(200).send(result);
    } catch (e) {
        handleError(e as Error, req, res);
    }
}
