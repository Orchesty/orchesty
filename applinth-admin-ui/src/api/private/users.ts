import { ApiConfigs } from "@/types";
import { apiClient } from "@/utils/apiClient";
import { UsersRows } from "../generated";

export type UsersApi = "list";
export type UsersGetApi = "get";
export type UsersCreateApi = "create";
export type UsersUpdateApi = "update";
export type UsersDeleteApi = "delete";
export type UsersGetTenantIdApi = "getTenantId";

export const users: ApiConfigs<
  | UsersApi
  | UsersCreateApi
  | UsersUpdateApi
  | UsersGetApi
  | UsersDeleteApi
  | UsersGetTenantIdApi
> = {
  list: {
    id: "USERS_LIST",
    request: (params) => apiClient.usersApi.usersList(params),
    transform: (data: UsersRows) => data.rows,
  },
  get: {
    id: "USERS_GET",
    request: (data) => apiClient.usersApi.usersGet(data),
  },
  create: {
    id: "USERS_CREATE",
    request: (data) => apiClient.usersApi.usersCreate(data),
  },
  update: {
    id: "USERS_UPDATE",
    request: (data) => apiClient.usersApi.usersUpdate(data),
  },
  delete: {
    id: "USERS_DELETE",
    request: (data) => apiClient.usersApi.usersDelete(data),
  },
  getTenantId: {
    id: "USERS_GET_TENANT_ID",
    request: (data) => apiClient.usersApi.userGetGTenantId(data),
    transform: (data) => data.gTenantId,
  },
};
