import { ApiConfigs } from "@/types";
import { apiClient } from "@/utils/apiClient";

export const tenants: ApiConfigs<"get"> = {
  get: {
    id: "TENANTS_GET",
    request: (params) => apiClient.tenantsApi.tenantsGet(params),
    transform: (data) => data.tenant,
  },
};
