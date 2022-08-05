import { TablesNamespaces } from "../../../store/modules/tables/types";
import { FilterOperatorEnum, SorterDirectionEnum } from "../../../types";
import { Rules } from "../../../utils/veeValidate";

export type TableHeaders<Table = any> = {
  text: string;
  align?: "start" | "center" | "end";
  sortable: boolean;
  value: keyof Table | "actions" | "button" | "totalPrice";
  width?: string;
}[];

export interface TableFilterItem<FilteredColumns extends string = string> {
  column: FilteredColumns;
  operator: FilterOperatorEnum;
  values: Array<string | number>;
}

export type TableFilter<FilteredColumns extends string = string> = Array<{
  filter: TableFilterItem<FilteredColumns>;
}>;

export interface TablePagerInput {
  page: number;
  size: number;
}

export type TableSorterInput = {
  column: string;
  direction: SorterDirectionEnum;
}[];

export interface TableOptions<
  Table = any,
  FilteredColumns extends string = string
> {
  namespace: TablesNamespaces;
  headers?: TableHeaders<Table>;
  defaultFilter?: TableFilter<FilteredColumns>;
  defaultPerPage?: number;
  defaultSortBy?: string[];
  defaultSortDesc?: boolean[];
  defaultSearch?: string;
  filterableColumns?: {
    value: FilteredColumns;
    text: string;
    type?: TableColumnDataType;
    enumValues?: EnumValues[];
  }[];
  refreshInterval?: number;
  saveTableState?: boolean;
  pageText?: string;
  itemPerPageText?: string;
  key?: string;
}

export interface Options {
  page: number;
  itemsPerPage: number;
  sortBy: string[];
  sortDesc: boolean[];
  groupBy: any[];
  groupDesc: boolean[];
  mustSort: boolean;
  multiSort: boolean;
}

export type TableQueryVariables = {
  input?: {
    filter?: TableFilter | [];
    sorter?: TableSorterInput | [];
    pager?: TablePagerInput | null;
    search?: string;
  };
};

export interface TableFetchPayload {
  ownRequestId?: string;
  namespace: TablesNamespaces;
  filter?: TableFilter | null;
  search?: string | null;
  pager?: TablePagerInput | null;
  sorter?: TableSorterInput | null;
}

export interface TableFilterPayload {
  namespace: TablesNamespaces;
  filter: TableFilter;
}

export interface TableSearchPayload {
  namespace: TablesNamespaces;
  search: string | null;
}

export interface TableRefreshPayload {
  namespace: TablesNamespaces;
  hideLoading?: boolean;
}

export interface TableChangePagingPayload {
  namespace: TablesNamespaces;
  pager: TablePagerInput;
}

export interface TableSortPayload {
  namespace: TablesNamespaces;
  sorter: TableSorterInput;
}

export type AdvancedFilterRules = {
  column: Rules;
  operator: Rules;
  value1: Rules;
  value2: Rules;
};

export type EnumValues = {
  value: any;
  name: string;
};

export enum TableColumnDataType {
  String = "string",
  Number = "number",
  DateTime = "datetime",
  Boolean = "boolean",
  Enum = "enum",
}
