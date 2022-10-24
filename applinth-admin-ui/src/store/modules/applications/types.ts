export const applicationsNamespace = "applications"

export enum ApplicationsMutations {
  FetchingApplicationsMetadata = "fetchingApplicationsMetadata",
  SetApplicationsMetadata = "setApplicationsMetadata",
}

export enum ApplicationsGetters {
  IsFetchingApplicationsMetadata = "isFetchingApplicationsMetadata",
  GetApplicationsMetadata = "getApplicationsMetadata",
}
