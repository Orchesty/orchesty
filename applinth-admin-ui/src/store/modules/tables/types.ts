export enum TablesNamespaces {
  UsersTable = "usersTable",
  CustomersTable = "customersTable",
  CustomersAppsTable = "customersAppsTable",
  CustomersBillingTable = "customersBillingTable",
}

export enum TablesActions {
  Fetch = "fetch",
  Filter = "filter",
  Search = "search",
  Refresh = "refresh",
  ChangePaging = "changePaging",
  ResetPaging = "resetPaging",
  Sort = "sort",
}

export enum TablesMutations {
  Update = "update",
  ItemsUpdate = "itemsUpdate",
}

export enum TableGetters {
  GetTotal = "getTotal",
  GetItems = "getItems",
}
