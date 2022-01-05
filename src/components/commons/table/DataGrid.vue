<template>
  <v-sheet>
    <!--Toolbar-->
    <v-row v-if="disableToolbar">
      <v-col cols="12">
        <slot name="toolbar" />
      </v-col>
    </v-row>

    <!--Filter-->
    <data-grid-filter
      v-if="!disableFilter"
      :params="{ namespace: namespace, params: requestParams, paging: paging, filter: filter }"
      :quick-filters="quickFilters"
      :on-filter="onFilterInternal"
      :on-clear="onClearInternal"
      :on-save="onSaveInternal"
      :headers="headers"
      :filter="filter"
      :filter-meta="filterMeta"
      :disable-advanced-filter="disabledAdvancedFilter"
      :default-setting="defaultSetting"
      :disable-hide-headers="disableHideHeaders"
      :on-columns-change-internal="onColumnsChangeInternal"
      :show-full-text-search="showFullTextSearch"
      :simple-filter="simpleFilter"
    />

    <!--Title & Searchbar-->
    <slot v-if="isUserTask" :contentEnabled="contentEnabled" name="groupActionButtons"> </slot>

    <!--Data Grid & Iterator-->
    <v-row>
      <v-col :flat="disableToolbar" cols="12" :lg="contentEnabled ? 4 : 12">
        <div>
          <v-data-iterator
            v-if="isIterator"
            v-model="selected"
            :headers="visibleHeaders"
            :items="items"
            :options.sync="options"
            :server-items-length="totalItems"
            :loading="isLoading"
            :footer-props="{
              'items-per-page-options': rowItemsPerPage,
              'disable-pagination': isLoading,
              'disable-items-per-page': isLoading,
            }"
            :hide-default-footer="disablePagination"
            :hide-default-header="disableHeaders"
            :item-key="itemKey"
            :show-select="showSelect"
            :show-expand="showExpand"
            :single-expand="singleExpand"
            :search="searchText"
            :extended-iterator="extendedIterator"
          >
            <template #loading>
              <v-row>
                <v-col cols="12" class="d-flex align-center justify-center">
                  <progress-bar-linear />
                </v-col>
              </v-row>
            </template>
            <template #body.prepend>
              <slot name="body.prepend" />
            </template>
            <template #default="props">
              <slot :items="props.items" />
              <slot name="extended" :items="props.items" />
            </template>
          </v-data-iterator>

          <v-card v-else elevation="0" outlined>
            <v-data-table
              v-model="selected"
              :headers="visibleHeadersTruncate(visibleHeaders)"
              :items="items"
              :options.sync="options"
              :server-items-length="totalItems"
              :loading="isLoading"
              :footer-props="{
                'items-per-page-options': rowItemsPerPage,
                'disable-pagination': isLoading,
                'disable-items-per-page': isLoading,
              }"
              :hide-default-footer="disablePagination"
              :hide-default-header="disableHeaders"
              :item-key="itemKey"
              :show-select="showSelect"
              :show-expand="showExpand"
              :single-expand="singleExpand"
              :search="searchText"
              :height="height"
            >
              <template #top>
                <slot name="top" />
              </template>
              <template v-for="item in visibleHeaders" #[`header.${item.value}`]="{ header }">
                {{ $t(header.text) }}
                <slot :header="header" name="header.append"></slot>
              </template>
              <template #no-data>
                <slot name="no-data" />
              </template>
              <template #body.prepend>
                <slot name="body.prepend" />
              </template>
              <template v-if="placeholder" #body>
                <slot name="body" />
              </template>
              <template #item="props">
                <tr
                  :key="props.index"
                  :class="activeIndex === props.index && activeIndexId === props.item.id ? 'selected-row' : ''"
                  class="primary-row"
                  @click="() => props.expand(!props.isExpanded)"
                >
                  <td v-if="showExpand" :style="props.isExpanded ? 'border-bottom: none' : ''">
                    <v-icon @click.stop="" @click="() => props.expand(!props.isExpanded)">
                      {{ props.isExpanded ? 'keyboard_arrow_up' : 'keyboard_arrow_down' }}
                    </v-icon>
                  </td>
                  <td v-if="showSelect">
                    <v-checkbox
                      v-model="props.isSelected"
                      class="ma-auto z-10"
                      primary
                      hide-details
                      dense
                      @change="
                        (value) => {
                          props.select(value)
                        }
                      "
                    />
                  </td>
                  <slot :items="props" :expanded="props.isExpanded" :isVisible="(key) => isItemVisible(key)" />
                </tr>
              </template>
              <template #expanded-item="expandedProps">
                <td
                  v-if="showExpand"
                  :colspan="expandedColspan"
                  class="py-2 px-5 expanded-background expanded-row error--text"
                >
                  <slot name="expand" :items="expandedProps" />
                </td>
              </template>
              <template #footer>
                <slot name="footer" />
              </template>
            </v-data-table>
          </v-card>
        </div>
      </v-col>
      <v-col v-if="contentEnabled" cols="12" lg="8">
        <slot name="content"> </slot>
      </v-col>
    </v-row>
  </v-sheet>
</template>

<script>
import { DIRECTION } from '../../../store/grid'
import { GRID } from '../../../store/grid/store/types'
import { withNamespace } from '../../../store/utils'
import DataGridFilter from './filter/DataGridFilter'
import ProgressBarLinear from '@/components/commons/progressIndicators/ProgressBarLinear'

export default {
  name: 'DataGrid',
  components: { ProgressBarLinear, DataGridFilter },
  props: {
    title: {
      type: String,
      default: '',
    },
    contentEnabled: {
      type: Boolean,
      default: false,
    },
    headers: {
      type: Array,
      required: true,
    },
    isIterator: {
      type: Boolean,
      default: false,
    },
    isLoading: {
      type: Boolean,
      required: true,
    },
    showSelect: {
      type: Boolean,
      default: false,
    },
    showFullTextSearch: {
      type: Boolean,
      default: false,
    },
    disableSearch: {
      type: Boolean,
      default: false,
    },
    disablePagination: {
      type: Boolean,
      default: false,
    },
    disableHeaders: {
      type: Boolean,
      default: false,
    },
    isUserTask: {
      type: Boolean,
      default: false,
    },
    disableToolbar: {
      type: Boolean,
      default: false,
    },
    disableHideHeaders: {
      type: Boolean,
      default: false,
    },
    disableFilter: {
      type: Boolean,
      default: false,
    },
    disabledAdvancedFilter: {
      type: Boolean,
      default: false,
    },
    itemKey: {
      type: String,
      default: 'id',
      required: false,
    },
    showExpand: {
      type: Boolean,
      default: false,
    },
    singleExpand: {
      type: Boolean,
      default: false,
    },
    namespace: {
      type: String,
      required: true,
    },
    quickFilters: {
      type: Array,
      default: () => [],
    },
    requestParams: {
      type: Object,
      default: null,
    },
    extendedIterator: {
      type: Boolean,
      default: false,
    },
    expandClick: {
      type: Boolean,
      default: false,
    },
    returnRowProps: {
      type: Boolean,
      default: false,
    },
    showActiveRow: {
      type: Boolean,
      default: false,
    },
    stats: {
      type: Boolean,
      default: false,
    },
    initialSearch: {
      type: String,
      required: false,
      default: () => '',
    },
    height: {
      type: String,
      default: () => 'auto',
    },
    fillHeight: {
      type: Boolean,
      default: false,
    },
    placeholder: {
      type: Boolean,
      default: false,
    },
    permanentFilter: {
      type: Array,
      default: () => [],
    },
    simpleFilter: {
      type: Boolean,
      default: false,
    },
  },
  data() {
    return {
      dataHeight: window.innerHeight - 350,
      activeIndex: null,
      activeIndexId: null,
      selected: [],
      searchText: '',
      options: {},
      dialog: false,
      rowItemsPerPage: [10, 20, 50, 100],
      visibleHeaders: [],
      searchTimeout: null,
    }
  },
  created() {
    this.visibleHeaders = this.getVisibleHeaders(this.headers)
    // default value from store
    this.options = {
      sortBy: this.sorter ? [this.sorter[0].column] : [],
      sortDesc: this.sorter ? [this.sorter[0].direction === DIRECTION.DESCENDING] : [],
      page: this.paging ? this.paging.page : 1,
      itemsPerPage: this.paging ? this.paging.itemsPerPage : 10,
    }
    this.searchText = this.search || ''
    if (this.initialSearch) {
      this.searchText = this.initialSearch
    }
    window.addEventListener('resize', this.resizeHandler)
  },
  destroyed() {
    window.removeEventListener('resize', this.resizeHandler)
  },
  computed: {
    items() {
      return this.$store.state[this.namespace].items
    },
    paging() {
      return this.$store.state[this.namespace].paging
    },
    filter() {
      return this.$store.state[this.namespace].filter
    },
    sorter() {
      return this.$store.state[this.namespace].sorter
    },
    search() {
      return this.$store.state[this.namespace].search
    },
    filterMeta() {
      return this.$store.state[this.namespace].filterMeta
    },
    defaultSetting() {
      return this.$store.state[this.namespace].default
    },
    headersMeta() {
      return this.$store.state[this.namespace].headersMeta
    },
    totalItems() {
      return this.paging.total
    },
    expandedColspan() {
      let colspan = this.headers.length

      if (this.showExpand) {
        colspan++
      }

      if (this.showSelect) {
        colspan++
      }

      return colspan
    },
  },
  watch: {
    options: {
      handler() {
        const { sortBy, sortDesc, page, itemsPerPage } = this.options
        const paging = {
          page: page,
          itemsPerPage: itemsPerPage,
        }

        let sorter = null
        if (sortBy.length > 0 && sortDesc.length > 0) {
          sorter = [
            {
              column: sortBy[0],
              direction: sortDesc[0] === true ? DIRECTION.DESCENDING : DIRECTION.ASCENDING,
            },
          ]
        }

        this.onPagingInternal({ paging, sorter })
      },
      deep: true,
    },
    selected(selected) {
      this.$emit('input', selected)
    },
    paging(paging) {
      this.options.page = paging.page
      this.options.itemsPerPage = paging.itemsPerPage
    },
    headers() {
      this.visibleHeaders = this.getVisibleHeaders(this.headers)
    },
    // items() {
    //   this.onRowClicked({ item: this.$store.state[this.namespace].items[0] })
    // },
    //DEFAULT SELECTION OF THE FIRST ITEM
  },
  methods: {
    visibleHeadersTruncate(headers) {
      if (!headers) {
        return
      }
      return headers.map((header) => {
        header.class = 'truncate'
        return header
      })
    },
    resizeHandler() {
      this.dataHeight = window.innerHeight - 350
    },
    async reload() {
      await this.$store.dispatch(withNamespace(this.namespace, GRID.ACTIONS.GRID_FILTER), {
        namespace: this.namespace,
        params: this.requestParams,
        paging: this.paging,
        filter: this.filter,
      })
    },
    onPagingInternal(args) {
      this.$store.dispatch(withNamespace(this.namespace, GRID.ACTIONS.GRID_CHANGE_PAGING), {
        namespace: this.namespace,
        params: this.requestParams,
        ...args,
      })
    },
    onSearchInternal(val) {
      this.$store.dispatch(withNamespace(this.namespace, GRID.ACTIONS.GRID_SEARCH), {
        // filter: this.filter,
        // filterMeta: this.filterMeta,
        namespace: this.namespace,
        params: this.requestParams,
        search: val,
      })
    },
    async onFilterInternal(filter, filterMeta, search, native) {
      if (this.permanentFilter) {
        if (!filter) {
          filter = []
        }
        filter = filter.concat(this.permanentFilter)
        if (search?.timeMargin) {
          filter = filter.concat([search.timeMargin])
        }
      }
      if (native) {
        native = JSON.parse(native)
      }
      let fullTextSearch = null
      if (search?.fullTextSearch) {
        fullTextSearch = search.fullTextSearch
      }

      await this.$store.dispatch(withNamespace(this.namespace, GRID.ACTIONS.GRID_FILTER), {
        namespace: this.namespace,
        filter,
        filterMeta,
        native,
        search: fullTextSearch,
        params: this.requestParams,
      })
      this.$emit('reset')
    },
    async onClearInternal() {
      await this.$store.dispatch(withNamespace(this.namespace, GRID.ACTIONS.GRID_FILTER_RESET), {
        namespace: this.namespace,
        params: this.requestParams,
      })
    },
    gridInit(params) {
      this.$store.dispatch(withNamespace(this.namespace, GRID.ACTIONS.GRID_FETCH), {
        namespace: this.namespace,
        filter: this.permanentFilter,
        params,
      })
    },
    onSaveInternal() {
      this.$store.dispatch(withNamespace(this.namespace, GRID.ACTIONS.GRID_STATE_SAVE), {
        namespace: this.namespace,
      })
    },
    onRowClicked(props) {
      // this.selected = [props.item]
      if (this.showActiveRow) {
        this.activeIndex = props.index
        this.activeIndexId = props.item.id
      }
      this.expandClick ? props.expand(!props.isExpanded) : null
      this.returnRowProps ? this.$emit('row-props', props) : null
    },
    onColumnsChangeInternal(headers) {
      this.visibleHeaders = this.getVisibleHeaders(headers)
      this.$store.dispatch(withNamespace(this.namespace, GRID.ACTIONS.GRID_HEADERS_SAVE), headers)
    },
    getVisibleHeaders(headers) {
      // restore headers
      this.headersMeta.forEach((column) => {
        let header = this.headers.find((header) => header.value === column.value)

        if (header && header.alwaysVisible !== true) {
          header.visible = column.visible
        }
      })

      return headers.filter((item) => item.visible === true || item.alwaysVisible === true)
    },
    isItemVisible(name) {
      const index = this.visibleHeaders.findIndex((item) => item.value === name)

      if (index !== -1) {
        return true
      }

      return false
    },
    clearSelected() {
      this.selected = []
    },
  },
}
</script>

<style lang="sass">
@import "~vuetify/src/styles/styles"
.primary-row > td:not(:first-child)
  text-align: center
.selected-row
  background-color: #eeeeee
.v-data-table-header
  background: var(--v-primary-base) !important
  th
    color: var(--v-white-base) !important
    .theme--light.v-icon
      color: var(--v-white-base) !important

.v-expansion-panel::before
  box-shadow: none

.v-data-table
  .expanded-background
    background: transparent
.z-10
  z-index: 10
.v-data-footer__pagination
  margin: 0 !important
.v-application--is-ltr .v-data-footer__select .v-select
  margin: 13px 0 13px 8px
</style>
