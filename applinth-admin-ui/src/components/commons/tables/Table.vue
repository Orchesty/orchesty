<template>
  <v-card outlined v-if="shouldBeVisible">
    <v-data-table
      class="data-table"
      :loading="loading"
      :headers="tableOptions.headers"
      :no-data-text="$t('table.noData')"
      :items="tableState.items"
      :items-per-page="tableOptions.defaultPerPage"
      :options.sync="tableStateOptions"
      :page.sync="tableState.pager.page"
      :hide-default-footer="hideFooter"
      :footer-props="{
        'items-per-page-options': itemsPerPage,
        'items-per-page-text': $t(
          tableOptions.itemPerPageText
            ? tableOptions.itemPerPageText
            : 'table.itemsPerPage'
        ),
        'page-text': $t(
          tableOptions.pageText ? tableOptions.pageText : 'table.numberOfPages',
          [tableState.pager.page, tableState.pager.last]
        ),
      }"
      :server-items-length="tableState.pager.total"
      single-select
    >
      <template v-slot:item="props">
        <tr @click="onRowClick(props)" :class="{ selected: props.isSelected }">
          <template v-for="(header, index) in tableOptions.headers">
            <td v-if="$scopedSlots[header.value.toString()]" :key="index">
              <slot
                :name="header.value"
                v-bind="props"
                :value="props.item[header.value]"
              />
            </td>
            <td
              v-else
              :class="header.align ? `text-${header.align}` : ''"
              :key="index"
            >
              {{ props.item[header.value] }}
            </td>
          </template>
        </tr>
      </template>
    </v-data-table>
  </v-card>
</template>

<script lang="ts">
import { Component, Prop, Vue, Watch } from "vue-property-decorator";
import store from "../../../store";
import { ApiGetters, apiNamespace } from "../../../store/modules/api/types";
import { TablesActions, TableState } from "../../../store/modules/tables";
import { Options, SorterDirectionEnum } from "../../../types";
import { userSettings } from "../../../utils/userSettings";
import {
  TableChangePagingPayload,
  TableFetchPayload,
  TableOptions,
  TablePagerInput,
  TableRefreshPayload,
  TableSorterInput,
  TableSortPayload,
} from "./types";

const DEFAULT_PER_PAGE = 20;

@Component
export default class Table extends Vue {
  @Prop({ required: true, type: Object })
  protected tableOptions!: TableOptions;

  @Prop({ required: false, type: Number, default: 12 })
  protected tableColumnWidth!: number;

  @Prop({ type: Array, required: false, default: () => [10, 20, 50, 100] })
  protected itemsPerPage!: number[];

  @Prop({ type: Boolean, default: false })
  protected hideFooter!: boolean;

  @Prop({ type: Boolean, default: false })
  protected hideOnNoData!: boolean;

  protected options: Options;

  protected refreshInterval?: number;

  protected hideLoading: boolean;

  constructor() {
    super();
    this.hideLoading = true;
    this.options = this.getInitDataOptions();
    this.callFetch(this.options);
    this.setRefreshInterval();
  }

  get shouldBeVisible() {
    if (this.hideOnNoData) {
      if (!this.tableState?.items?.length) {
        return false;
      }
    }
    return true;
  }

  get tableState(): TableState {
    return store.state[this.tableOptions.namespace];
  }

  get tableStateOptions(): Options {
    const state = store.state[this.tableOptions.namespace];

    this.options.itemsPerPage = state.pager.size;

    return this.options;
  }

  set tableStateOptions(options: Options) {
    this.options = options;
  }

  get loading(): boolean {
    const isSending = store.getters[`${apiNamespace}/${ApiGetters.IsSending}`];
    return isSending(`TABLE/${this.tableOptions.namespace}`);
  }

  protected getInitDataOptions(): Options {
    const options = {
      page: 1,
      itemsPerPage: this.tableOptions.defaultPerPage ?? DEFAULT_PER_PAGE,
      sortBy: this.tableOptions.defaultSortBy ?? [],
      sortDesc: this.tableOptions.defaultSortDesc ?? [],
      groupBy: [],
      groupDesc: [],
      mustSort: false,
      multiSort: false,
    };

    if (this.tableOptions.saveTableState) {
      const tableLastState = userSettings.getTableLastState(
        this.tableOptions.namespace
      );

      if (tableLastState.pager) {
        options.page = tableLastState.pager.page;
        options.itemsPerPage = tableLastState.pager.size;
      }

      if (tableLastState.sorter && tableLastState.sorter.length) {
        options.sortBy = [tableLastState.sorter[0].column];
        options.sortDesc = [
          tableLastState.sorter[0].direction === SorterDirectionEnum.Descending,
        ];
      }
    }

    return options;
  }

  protected callFetch(options: Options): void {
    let sorter: TableSorterInput = [];
    options.sortBy.forEach((item, index) => {
      const direction = options.sortDesc[index]
        ? SorterDirectionEnum.Descending
        : SorterDirectionEnum.Ascending;
      const sort = { column: item.toUpperCase(), direction: direction };
      sorter.push(sort);
    });

    let search = this.tableOptions.defaultSearch;
    let filter = this.tableOptions.defaultFilter;

    if (this.tableOptions.saveTableState) {
      const tableLastState = userSettings.getTableLastState(
        this.tableOptions.namespace
      );

      if (tableLastState.filter) {
        filter = tableLastState.filter;
      }

      if (tableLastState.sorter) {
        sorter = tableLastState.sorter;
      }

      if (tableLastState.search) {
        search = tableLastState.search;
      }
    }

    store.dispatch(`${this.tableOptions.namespace}/${TablesActions.Fetch}`, {
      namespace: this.tableOptions.namespace,
      filter: filter,
      search: search,
      pager: {
        page: options.page,
        size: options.itemsPerPage,
      },
      sorter: sorter,
    } as TableFetchPayload);
  }

  protected setRefreshInterval(): void {
    if (!this.tableOptions.refreshInterval) return;

    this.refreshInterval = setInterval(() => {
      store.dispatch(
        `${this.tableOptions.namespace}/${TablesActions.Refresh}`,
        {
          namespace: this.tableOptions.namespace,
          hideLoading: this.hideLoading,
        } as TableRefreshPayload
      );
    }, this.tableOptions.refreshInterval * 1000);
  }

  @Watch("options.page")
  @Watch("options.itemsPerPage")
  protected handlePagerChange(): void {
    if (
      this.options.page === this.tableState.pager.page &&
      this.options.itemsPerPage === this.tableState.pager.size
    )
      return;

    const newPager: TablePagerInput = {
      page: this.options.page,
      size: this.options.itemsPerPage,
    };

    store.dispatch(
      `${this.tableOptions.namespace}/${TablesActions.ChangePaging}`,
      {
        namespace: this.tableOptions.namespace,
        pager: newPager,
      } as TableChangePagingPayload
    );

    if (this.tableOptions.saveTableState) {
      userSettings.updateTableLastState(this.tableOptions.namespace, {
        ...userSettings.getTableLastState(this.tableOptions.namespace),
        pager: newPager,
      });
    }
  }

  @Watch("options", { deep: true })
  protected handleSorterChange(newVal: Options, prevVal: Options): void {
    if (
      newVal.sortBy[0] === prevVal.sortBy[0] &&
      newVal.sortDesc[0] === prevVal.sortDesc[0]
    )
      return;
    let newSorter: TableSorterInput;
    if (!newVal.sortBy[0]) {
      newSorter = [];
    } else {
      newSorter = [
        {
          column: newVal.sortBy[0].toUpperCase(),
          direction: newVal.sortDesc[0]
            ? SorterDirectionEnum.Descending
            : SorterDirectionEnum.Ascending,
        },
      ];
    }
    store.dispatch(`${this.tableOptions.namespace}/${TablesActions.Sort}`, {
      namespace: this.tableOptions.namespace,
      sorter: newSorter,
    } as TableSortPayload);

    if (this.tableOptions.saveTableState) {
      userSettings.updateTableLastState(this.tableOptions.namespace, {
        ...userSettings.getTableLastState(this.tableOptions.namespace),
        sorter: newSorter,
      });
    }
  }

  onRowClick(props: any) {
    this.$emit("click:row", props);
  }

  public beforeDestroy(): void {
    if (this.refreshInterval) clearInterval(this.refreshInterval);
  }
}
</script>

<style lang="scss" scoped>
.data-table {
  ::v-deep .sortable {
    white-space: nowrap;
  }

  ::v-deep .text-start {
    white-space: nowrap;
  }

  ::v-deep .actions {
    justify-content: flex-end;
  }
}
</style>
