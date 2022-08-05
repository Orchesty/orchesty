import { AxiosRequestConfig } from "axios";
import { ErrorType } from "../enums";

export interface ApiConfig<V = any> {
  id: string;
  request: (variables?: any) => AxiosRequestConfig;
  errorType?: ErrorType;
  throwError?: boolean;
  variables?: V;
  mock?: object | (() => object);
}

export type ApiConfigs<T extends string> = { [index in T]: ApiConfig };
