import { apiClient } from "@/utils/apiClient";
import { ApiConfigs } from "@/types";
import { UsageStatsUsers } from "@/api/generated";

export type ApplicationsApi = "application";

export const customers: ApiConfigs<ApplicationsApi> = {
  application: {
    id: "APPLICATION_DETAIL",
    request: (params) => apiClient.billingApi.usageStatsUsers(params),
    transform: (data: UsageStatsUsers) => data.rows,
  },
};
