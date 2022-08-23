import { api } from "../../../api";
import { ApiConfig } from "../../../types";
import { TablesNamespaces } from "./types";

interface Config {
  apiConfig: ApiConfig;
  reduceData: (data: any) => any;
}
type TablesConfig = { [index in TablesNamespaces]: Config };

export const tablesConfig: TablesConfig = {
  usersTable: {
    apiConfig: api.users.list,
    reduceData: (data) => data,
  },
  customersTable: {
    apiConfig: api.customers.list,
    reduceData: (data) => data,
  },
  customersAppsTable: {
    apiConfig: api.customers.list,
    reduceData: (data) => data,
  },
  customersBillingTable: {
    apiConfig: api.customers.list,
    reduceData: (data) => data,
  },
};
