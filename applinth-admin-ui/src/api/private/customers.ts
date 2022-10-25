import { apiClient } from "@/utils/apiClient"
import { ApiConfigs } from "@/types"
import { UsageStatsUsers } from "../generated"

export type CustomersApi = "list"

export const customers: ApiConfigs<CustomersApi> = {
  list: {
    id: "CUSTOMERS_LIST",
    request: (params) => apiClient.billingApi.usageStatsUsers(params),
    transform: (data: UsageStatsUsers | undefined) => data?.rows || [],
  },
}
