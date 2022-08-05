import { TableFilter, TablePagerInput, TableSorterInput } from ".";
import { TablesNamespaces } from "../store/modules/tables/types";

export interface QuickFilter {
  id: number;
  name: string;
  filter: TableFilter;
}

export interface TableLastState {
  activeQuickFilter: QuickFilter["id"] | null;
  filter: TableFilter | null;
  search: string | null;
  pager: TablePagerInput | null;
  sorter: TableSorterInput | null;
}

export interface TableSettings {
  expandedAdvancedFilter: boolean;
  quickFilters: QuickFilter[];
  lastState: TableLastState;
}

export type ProjectNamespaceTables = {
  [index in TablesNamespaces]: TableSettings;
};

export interface IUserSettings {
  version: number;
  navMiniVariant: boolean;
  tables: ProjectNamespaceTables;
  expandedCards: { [key: string]: boolean | undefined };
  expandedNavSubmenus: { [key: string]: boolean | undefined };
}
