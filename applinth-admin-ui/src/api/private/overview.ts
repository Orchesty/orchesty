import { apiClient } from "@/utils/apiClient"
import { ApiConfigs } from "@/types"
import {
  UsageStatsApps,
  UsageStatsInstalledApps,
  UsageStatsTimeBucketApps,
  UsageStatsTimeBucketUsers,
  UsageStatsUsers,
} from "@/api/generated"

export type OverviewApi = "apps"
export type OverviewApiFull = "full"
export type OverviewTimeBucketUsersApi = "data"

export const overview: ApiConfigs<OverviewApi> = {
  apps: {
    id: "OVERVIEW_APPS",
    request: (params) => apiClient.billingApi.usageStatsApps(params),
    transform: (data: UsageStatsApps | undefined) => data?.rows || [],
  },
}

export const overviewFull: ApiConfigs<OverviewApiFull> = {
  full: {
    id: "OVERVIEW_APPS",
    request: (params) => apiClient.billingApi.usageStatsApps(params),
  },
}

export const timeBucketUsers: ApiConfigs<OverviewTimeBucketUsersApi> = {
  data: {
    id: "OVERVIEW_APP_TIME_BUCKET_USERS",
    request: (params) => apiClient.billingApi.usageStatsTimeBucketUsers(params),
    transform: (data: UsageStatsTimeBucketUsers | undefined) =>
      data?.rows || [],
  },
}

export const installedApps: ApiConfigs<OverviewApi> = {
  apps: {
    id: "OVERVIEW_INSTALLED_APPS",
    request: (params) => apiClient.billingApi.usageStatsInstalledApps(params),
    transform: (data: UsageStatsInstalledApps | undefined) => data?.rows || [],
  },
}

export const timeBucketApps: ApiConfigs<OverviewApi> = {
  apps: {
    id: "OVERVIEW_TIME_BUCKET_APPS",
    request: (params) => apiClient.billingApi.usageStatsTimeBucketApps(params),
    transform: (data: UsageStatsTimeBucketApps | undefined) => data?.rows || [],
  },
}

export const overviewUsers: ApiConfigs<OverviewApi> = {
  apps: {
    id: "OVERVIEW_USERS",
    request: (params) => apiClient.billingApi.usageStatsUsers(params),
    transform: (data: UsageStatsUsers | undefined) => data?.rows || [],
  },
}

export const timeBucketHistory: ApiConfigs<"data"> = {
  data: {
    id: "TIME_BUCKET_HISTORY",
    request: (params) =>
      apiClient.billingApi.usageStatsTimeBucketHistory(params),
  },
}
