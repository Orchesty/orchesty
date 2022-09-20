import { apiClient } from "@/utils/apiClient";
import { ApiConfigs } from "../../types";
import { UsageStatsApps, UsageStatsInstalledApps } from "../generated";

export type OverviewApi = "apps";
export type OverviewTimeBucketUsersApi = "data";

// todo PIP-1296
// a = () => apiClient.billingApi.usageStatsUsers()

export const overview: ApiConfigs<OverviewApi> = {
  apps: {
    id: "OVERVIEW_APPS",
    request: (params) => apiClient.billingApi.usageStatsApps(params),
    transform: (data: UsageStatsApps) => data.rows,
  },
};

export const timeBucketUsers: ApiConfigs<OverviewTimeBucketUsersApi> = {
  data: {
    id: "OVERVIEW_APP_TIME_BUCKET_USERS",
    request: (params) => apiClient.billingApi.usageStatsTimeBucketUsers(params),
  },
};

export const installedApps: ApiConfigs<OverviewApi> = {
  apps: {
    id: "OVERVIEW_INSTALLED_APPS",
    request: (params) => apiClient.billingApi.usageStatsInstalledApps(params),
    transform: (data: UsageStatsInstalledApps) => data.rows,
  },
};

export const timeBucketApps: ApiConfigs<OverviewApi> = {
  apps: {
    id: "OVERVIEW_TIME_BUCKET_APPS",
    request: (params) => apiClient.billingApi.usageStatsTimeBucketApps(params),
  },
};
