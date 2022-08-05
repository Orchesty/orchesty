import axios, { AxiosInstance, AxiosResponse } from "axios";
import store from "../store";
import { ApiMutations, apiNamespace } from "../store/modules/api";
import { ApiConfig } from "../types";
import { config } from "../config";
import { alerts } from "./alerts";
import { AuthGetters, authNamespace } from "../store/modules/auth";
import { ApiError, ErrorType } from "../enums";

class ApiClient {
  public async callGraphqlPublic<R = any, V = any>(
    apiConfig: ApiConfig<V>
  ): Promise<AxiosResponse<R | undefined>> {
    const axiosClient = axios.create({
      baseURL: config.backend.graphqlBaseUrl,
      withCredentials: true,
    });
    return this.call(axiosClient, apiConfig);
  }

  public async callGraphqlPrivate<R = any, V = any>(
    apiConfig: ApiConfig<V>
  ): Promise<AxiosResponse<R | undefined>> {
    const axiosClient = axios.create({
      baseURL: config.backend.graphqlPrivateUrl,
      withCredentials: true,
      headers: {
        Authorization:
          store.getters[`${authNamespace}/${AuthGetters.GetAccessToken}`],
      },
    });
    return this.call(axiosClient, apiConfig);
  }

  public callRest<R = any, V = any>(
    apiConfig: ApiConfig<V>
  ): Promise<AxiosResponse<R | undefined>> {
    const axiosClient = axios.create({
      baseURL: config.backend.apiBaseUrl,
    });
    return this.call(axiosClient, apiConfig);
  }

  private async call(axiosClient: AxiosInstance, apiConfig: ApiConfig) {
    if (apiConfig.mock) {
      return {
        data: apiConfig.mock,
      };
    }
    const axiosConfig = apiConfig.request(apiConfig.variables);
    try {
      this.startSending(apiConfig);
      const { data } = await axiosClient(axiosConfig);
      if (data.errors && data.errors.length) {
        if (apiConfig.throwError) throw new Error(ApiError.InternalServerError);
        this.errorSending(
          apiConfig,
          data.errors[0]?.message || ApiError.InternalServerError
        );
      }
      return data;
    } catch (err: any) {
      // Try to parse the error message from Graphql response,
      // or use Axios message, which is only based on request status
      const errorMessage =
        err.response?.data?.errors[0]?.message ?? err.message;
      if (err instanceof Error) {
        this.errorSending(apiConfig, errorMessage);
        if (apiConfig.throwError) {
          throw err;
        } else {
          return { data: undefined };
        }
      }
    } finally {
      this.stopSending(apiConfig);
    }
  }

  private startSending(apiConfig: ApiConfig): void {
    store.commit(`${apiNamespace}/${ApiMutations.StartSending}`, {
      id: apiConfig.id,
    });
  }

  private stopSending(apiConfig: ApiConfig): void {
    store.commit(`${apiNamespace}/${ApiMutations.StopSending}`, {
      id: apiConfig.id,
    });
  }

  private errorSending(apiConfig: ApiConfig, errorMessage: string): void {
    store.commit(`${apiNamespace}/${ApiMutations.ErrorSending}`, {
      id: apiConfig.id,
      error: errorMessage,
    });
    if (apiConfig.errorType === ErrorType.None) {
      alerts.addHiddenAlert(apiConfig.id, errorMessage);
    } else {
      alerts.addErrorAlert(apiConfig.id, errorMessage);
    }
  }
}

export const apiClient = new ApiClient();
