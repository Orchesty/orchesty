import { ApiConfigs } from "../../types";
import { apiClient } from "@/utils/apiClient";
import { UsersRows } from "../generated";

export type UsersApi = "list";

export const users: ApiConfigs<UsersApi> = {
  list: {
    id: "USERS_LIST",
    request: (params) => apiClient.usersApi.usersList(params),
    transform: (data: UsersRows) => data.rows,
  },
};
