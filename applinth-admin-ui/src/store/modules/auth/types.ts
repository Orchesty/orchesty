export const authNamespace = "auth";

export enum AuthActions {
  Login = "login",
  RefreshToken = "refreshToken",
  UpdateSettings = "updateSettings",
}

export enum AuthMutations {
  SetAccessToken = "setAccessToken",
  SetAdministrator = "setAdministrator",
}

export enum AuthGetters {
  GetAccessToken = "getAccessToken",
  GetRawSettings = "getRawSettings",
  GetAdministrator = "getAdministrator",
  GetFullName = "getFullName",
}
