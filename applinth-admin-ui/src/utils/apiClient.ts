import { alerts } from "@/utils/alerts"
import {
  BillingApi,
  Configuration,
  ConfigurationParameters,
  HealthCheckApi,
  ResponseError,
  TenantsApi,
  UsersApi,
} from "../api/generated"
import { config } from "../config"
import { ApiConfig } from "../types"

const configParams: ConfigurationParameters = {
  basePath: config.backend.apiBaseUrl,
  middleware: [],
}

let apiConfig = new Configuration(configParams)

export let apiClient = initApiConfig()

export type ApiClient = typeof apiClient

function initApiConfig() {
  return {
    billingApi: new BillingApi(apiConfig),
    healthCheckApi: new HealthCheckApi(apiConfig),
    tenantsApi: new TenantsApi(apiConfig),
    usersApi: new UsersApi(apiConfig),
  }
}

export function assignTokenToApiCall(token: string) {
  apiConfig = new Configuration({
    basePath: config.backend.apiBaseUrl,
    middleware: [],
    headers: { ["authorization"]: `Bearer ${token}` },
  })
  apiClient = initApiConfig()
}

export async function callApi<P>(
  apiConfig: ApiConfig<any>,
  requestParams: P | undefined = undefined
): Promise<any> {
  if (apiConfig.mock) {
    return Promise.resolve(apiConfig.mock)
  }

  let response
  try {
    response = await apiConfig.request(requestParams)
  } catch (err: any) {
    if (err instanceof ResponseError) {
      const errorData = await err.response.json()

      if (errorData.msg) {
        // error message from our BE
        alerts.addErrorAlert("API: ResponseError", errorData.msg)
      } else {
        const msg = `${err.name}: ${err.response.statusText} (${err.response.status})`
        alerts.addErrorAlert("API: ResponseError", msg)
      }
    } else {
      alerts.addErrorAlert("API: ResponseError", err)
    }
  }

  if (!response) {
    throw new Error("Response is empty")
  }

  return apiConfig.transform ? apiConfig.transform(response) : response
}
