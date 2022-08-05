<template>
  <v-text-field
    prepend-inner-icon="mdi-magnify"
    background-color="light-grey"
    filled
    flat
    solo
    dense
    hide-details
    v-model="search"
    :placeholder="$t('table.search')"
  />
</template>

<script lang="ts">
import { Component, Prop, Vue, Watch } from "vue-property-decorator";
import store from "../../../../store";
import { TablesActions, TableState } from "../../../../store/modules/tables";
import { userSettings } from "../../../../utils/userSettings";
import { TableOptions, TableSearchPayload } from "../types";

@Component
export default class SearchFilter extends Vue {
  @Prop({ required: true, type: Object })
  private tableOptions!: TableOptions;

  private search = "";
  private timeout?: number;

  constructor() {
    super();
    this.search = this.getInitSearch();
  }

  get currentSearchState(): TableState["search"] {
    return store.state[this.tableOptions.namespace].search;
  }

  private getInitSearch(): string {
    let search = this.tableOptions.defaultSearch || "";

    if (this.tableOptions.saveTableState) {
      const lastState = userSettings.getTableLastState(
        this.tableOptions.namespace
      );
      if (lastState.search) {
        search = lastState.search;
      }
    }

    return search;
  }

  @Watch("search")
  private onChangeSearch(newVal: string): void {
    if (this.timeout) clearTimeout(this.timeout);
    this.timeout = setTimeout(() => {
      if (newVal !== this.currentSearchState) {
        this.handleSaveUserSettings(newVal);
        this.callSearch(newVal);
      }
    }, 300);
  }

  private handleSaveUserSettings(search: string): void {
    if (this.tableOptions.saveTableState) {
      userSettings.updateTableLastState(this.tableOptions.namespace, {
        ...userSettings.getTableLastState(this.tableOptions.namespace),
        search,
      });
    }
  }

  private callSearch(search: string): void {
    store.dispatch(`${this.tableOptions.namespace}/${TablesActions.Search}`, {
      namespace: this.tableOptions.namespace,
      search,
    } as TableSearchPayload);
  }
}
</script>

<style lang="scss" scoped></style>
