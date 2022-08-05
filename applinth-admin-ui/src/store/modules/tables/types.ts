export enum TablesNamespaces {
  AdminsTable = "adminsTable",
  UsersTable = "usersTable",
  CustomersTable = "customersTable",
  CustomersAppsTable = "customersAppsTable",
  CustomersBillingTable = "customersBillingTable",
  DevicesTable = "devicesTable",
  LaborerEventsTable = "laborerEventsTable",
  LaborerTicketsTable = "laborerTicketsTable",
  LaborersTable = "laborersTable",
  MaintenancesTable = "maintenancesTable",
  OperationTemplatesTable = "operationTemplatesTable",
  PinnedTicketsTable = "pinnedTicketsTable",
  ProcessSubCodesTable = "processSubCodesTable",
  ProcessCodesTable = "processCodesTable",
  ProcessTemplatesTable = "processTemplatesTable",
  RegularMaintenancesTable = "regularMaintenancesTable",
  TicketsTable = "ticketsTable",
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
