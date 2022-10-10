import { Request, Response } from 'express';
import { ResourceEnum } from '../enums/ResourceEnum';
import handleError from '../handlers/errorHandler';
import { usageStatsService } from '../index';
import { preprocessRequest } from '../security/securityService';

export interface IAppsAggregationParams {
    tenantId?: string;
    timeRangeStart?: string;
    timeRangeEnd?: string;
    endUserId?: string;
    endUserDisplayId?: string;
    instanceId?: string;
    appId?: string;
    installedDate?: string;
    granularity?: string;
}

export async function usageStatsApps(req: Request, res: Response): Promise<void> {
    try {
        const { query, tenantId } = await preprocessRequest<IAppsAggregationParams>(req, ResourceEnum.LIST_USAGE_STATS);
        const result = await usageStatsService.getDataForAppsAggregation(query, tenantId);

        res.status(200).send(result);
    } catch (e) {
        handleError(e as Error, req, res);
    }
}

export async function usageStatsInstalledApps(req: Request, res: Response): Promise<void> {
    try {
        const { query, tenantId } = await preprocessRequest<IAppsAggregationParams>(req, ResourceEnum.LIST_USAGE_STATS);
        const result = await usageStatsService.getDataForInstalledAppsAggregation(query, tenantId);

        res.status(200).send(result);
    } catch (e) {
        handleError(e as Error, req, res);
    }
}

export async function usageStatsTimeBucketApps(req: Request, res: Response): Promise<void> {
    try {
        const { query, tenantId } = await preprocessRequest<IAppsAggregationParams>(req, ResourceEnum.LIST_USAGE_STATS);
        const result = await usageStatsService.getDataForTimeBucketAppsAggregation(query, tenantId);

        res.status(200).send(result);
    } catch (e) {
        handleError(e as Error, req, res);
    }
}

export async function usageStatsTimeBucketUsers(req: Request, res: Response): Promise<void> {
    try {
        const { query, tenantId } = await preprocessRequest<IAppsAggregationParams>(req, ResourceEnum.LIST_USAGE_STATS);
        const result = await usageStatsService.getDataForTimeBucketUsersAggregation(query, tenantId);

        res.status(200).send(result);
    } catch (e) {
        handleError(e as Error, req, res);
    }
}

export async function usageStatsUsers(req: Request, res: Response): Promise<void> {
    try {
        const { query, tenantId } = await preprocessRequest<IAppsAggregationParams>(req, ResourceEnum.LIST_USAGE_STATS);
        const result = await usageStatsService.getDataForUsersAggregation(query, tenantId);

        res.status(200).send(result);
    } catch (e) {
        handleError(e as Error, req, res);
    }
}
