export const apiNamespace = "api";

export enum ApiActions {}

export enum ApiMutations {
  StartSending = "startSending",
  StopSending = "stopSending",
  ErrorSending = "errorSending",
}

export enum ApiGetters {
  GetRequestDetails = "getRequestDetails",
  IsSending = "isSending",
}
