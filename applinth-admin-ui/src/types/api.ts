import { ErrorType } from "../enums"

export interface ApiConfig<V = unknown> {
  id: string
  request: (...variables: any) => Promise<unknown>
  transform?: (data: any) => unknown
  errorType?: ErrorType
  throwError?: boolean
  variables?: V
  mock?: object | (() => object)
}

export type ApiConfigs<T extends string> = { [index in T]: ApiConfig }
