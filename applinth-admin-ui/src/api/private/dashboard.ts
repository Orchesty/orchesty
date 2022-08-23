import { apiClient } from "@/utils/apiClient";
import { ApiConfigs } from "../../types";

export type DashboardApi = "status";

export const dashboard: ApiConfigs<DashboardApi> = {
  status: {
    id: "DASHBOARD_STATUS",
    request: (start: any, end: any) =>
      apiClient.billingApi.usageStatsApps({
        timeRangeStart: start,
        timeRangeEnd: end,
      }),
    transform: (data) => data,
  },
};
