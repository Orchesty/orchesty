import { apiClient } from "@/utils/apiClient";
import { ApiConfigs } from "../../types";
import { UsageStatsApps } from "../generated";

export type OverviewApi = "apps";

// a = () => apiClient.billingApi.usageStatsUsers()
// a = () => apiClient.billingApi.usageStatsInstalledApps()
// a = () => apiClient.billingApi.usageStatsTimeBucketApps()
// a = () => apiClient.billingApi.usageStatsTimeBucketUsers()

export const overview: ApiConfigs<OverviewApi> = {
  apps: {
    id: "OVERVIEW_APPS",
    request: (params) => apiClient.billingApi.usageStatsApps(params),
    transform: (data: UsageStatsApps) => data.rows,
  },
  // stats: {
  //   ??
  // },
};
