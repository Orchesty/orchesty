import store from "../store";
import {
  AuthActions,
  AuthGetters,
  authNamespace,
} from "../store/modules/auth/types";
import { TablesNamespaces } from "../store/modules/tables/types";
import { IUserSettings, TableLastState, TableSettings } from "../types";
import merge from "lodash.merge";
import { Theme } from "../enums/Theme";

export class UserSettings {
  get defaultTableSettings(): TableSettings {
    return {
      expandedAdvancedFilter: false,
      lastState: {
        activeQuickFilter: null,
        filter: null,
        search: null,
        pager: null,
        sorter: null,
      },
      quickFilters: [],
    };
  }

  get defaultSettings(): IUserSettings {
    const tables: IUserSettings["tables"] = (() => {
      const _tables: any = {};
      Object.values(TablesNamespaces).forEach((namespace) => {
        _tables[namespace] = this.defaultTableSettings;
      });

      return _tables;
    })();
    return {
      version: 1,
      navMiniVariant: false,
      tables,
      expandedCards: {},
      expandedNavSubmenus: {},
    };
  }

  get settings(): IUserSettings {
    const rawSettings: string =
      store.getters[`${authNamespace}/${AuthGetters.GetRawSettings}`];
    let partialSettings: Partial<IUserSettings>;
    try {
      partialSettings = JSON.parse(rawSettings);
    } catch {
      partialSettings = this.defaultSettings;
    }
    return merge(this.defaultSettings, partialSettings);
  }

  public getNavMiniVariant(): boolean {
    return this.settings.navMiniVariant;
  }

  public updateNavMiniVariant(newVal: boolean): void {
    this.callSettingsUpdate({
      ...this.settings,
      navMiniVariant: newVal,
    });
  }

  public getTableExpandedAdvancedFilter(
    namespace: TablesNamespaces
  ): TableSettings["expandedAdvancedFilter"] {
    return this.settings.tables[namespace].expandedAdvancedFilter;
  }

  public updateTableExpandedFilters(
    namespace: TablesNamespaces,
    expandedAdvancedFilter: boolean
  ): void {
    this.callSettingsUpdate({
      ...this.settings,
      tables: {
        ...this.settings.tables,
        [namespace]: {
          ...this.settings.tables[namespace],
          expandedAdvancedFilter,
        },
      },
    });
  }

  public getTableQuickFilters(
    namespace: TablesNamespaces
  ): TableSettings["quickFilters"] {
    return this.settings.tables[namespace].quickFilters;
  }

  public updateTableQuickFilters(
    namespace: TablesNamespaces,
    quickFilters: TableSettings["quickFilters"],
    activeQuickFilter?: TableLastState["activeQuickFilter"]
  ): void {
    const newSettings = {
      ...this.settings,
      tables: {
        ...this.settings.tables,
        [namespace]: {
          ...this.settings.tables[namespace],
          quickFilters,
        },
      },
    };
    if (activeQuickFilter || activeQuickFilter === null) {
      newSettings.tables[namespace].lastState.activeQuickFilter =
        activeQuickFilter;
    }
    this.callSettingsUpdate(newSettings);
  }

  public getTableLastState(namespace: TablesNamespaces): TableLastState {
    return this.settings.tables[namespace].lastState;
  }

  public updateTableLastState(
    namespace: TablesNamespaces,
    lastState: TableLastState
  ): void {
    this.callSettingsUpdate({
      ...this.settings,
      tables: {
        ...this.settings.tables,
        [namespace]: {
          ...this.settings.tables[namespace],
          lastState,
        },
      },
    });
  }

  public getNavSubmenuExpanded(submenuId: string): boolean {
    const expanded = this.settings.expandedNavSubmenus[submenuId];
    if (typeof expanded === "undefined") {
      return true;
    } else {
      return expanded;
    }
  }

  public updateNavSubmenuExpanded(submenuId: string, expanded: boolean): void {
    this.callSettingsUpdate({
      ...this.settings,
      expandedNavSubmenus: {
        ...this.settings.expandedNavSubmenus,
        [submenuId]: expanded,
      },
    });
  }

  public getCardExpanded(cardId: string): boolean {
    const expanded = this.settings.expandedCards[cardId];
    if (typeof expanded === "undefined") {
      return true;
    } else {
      return expanded;
    }
  }

  public updateCardExpanded(cardId: string, expanded: boolean): void {
    this.callSettingsUpdate({
      ...this.settings,
      expandedCards: {
        ...this.settings.expandedCards,
        [cardId]: expanded,
      },
    });
  }

  public getTheme(): Theme {
    return Theme.Light;
  }

  private callSettingsUpdate(newSettings: IUserSettings): Promise<any> {
    return store.dispatch(
      `${authNamespace}/${AuthActions.UpdateSettings}`,
      JSON.stringify(newSettings)
    );
  }
}

export const userSettings = new UserSettings();
