import { apiClient } from "@/utils/apiClient";
import { ApiConfigs } from "../../types";
import { UsageStatsApps } from "../generated";

export type OverviewApi = "apps";
export type OverviewTimeBucketUsersApi = "data";

// a = () => apiClient.billingApi.usageStatsUsers()
// a = () => apiClient.billingApi.usageStatsInstalledApps()
// a = () => apiClient.billingApi.usageStatsTimeBucketApps()

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
    // transform: (data: UsageStatsApps) => data.rows,
  },
};
