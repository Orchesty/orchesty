<template>
  <v-sheet>
    <!--Filter-->
    <data-grid-filter
      :disable-filter="disableFilter"
      :params="{ namespace: namespace, params: fixedParams, paging: paging, filter: filter }"
      :quick-filters="quickFilters"
      :on-filter="fetchGrid"
      :on-clear="fetchGridWithInitials"
      :on-reload="fetchGrid"
      :headers="headers"
      :filter="filter"
      :filter-meta="filterMeta"
      :simple-filter="simpleFilter"
      :simple-filter-enum="simpleFilterEnum"
      :is-loading="isLoading"
    />

    <!--Title & Searchbar-->
    <slot v-if="twoColumnLayout" :content-enabled="contentEnabled" name="groupActionButtons"> </slot>

    <!--Data Grid & Iterator-->
    <v-row dense>
      <v-col cols="12" :lg="contentEnabled ? 4 : 12">
        <h3 v-if="title" class="mb-2 title">{{ title }}</h3>
        <v-card elevation="0" outlined>
          <v-data-table
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
          >
            <template #top>
              <slot name="top" />
            </template>
            <template v-for="item in visibleHeaders" #[`header.${item.value}`]="{ header }">
              <span :key="item.value" class="text-capitalize white--text font-weight-bold">{{ $t(header.text) }}</span>
              <slot :header="header" name="header.append"></slot>
            </template>
            <template #no-data>
              <slot name="no-data" />
            </template>
            <template #body.prepend>
              <slot name="body.prepend" />
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
                <slot :items="props" :expanded="props.isExpanded" :is-visible="(key) => isItemVisible(key)" />
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
      </v-col>
      <v-col v-if="contentEnabled" cols="12" lg="8">
        <slot name="content"> </slot>
      </v-col>
    </v-row>
  </v-sheet>
</template>

<script>
import { GRID } from '../../../store/modules/grid/types'
import { withNamespace } from '../../../store/utils'
import DataGridFilter from './filter/DataGridFilter'
import { SIMPLE_FILTER } from '@/services/enums/dataGridFilterEnums'
import { DIRECTION } from '@/services/enums/gridEnums'

export default {
  name: 'DataGrid',
  components: { DataGridFilter },
  props: {
    fixedItems: {
      type: Array,
      default: null,
    },
    fixedParams: {
      type: Object,
      default: null,
    },
    fixedFilter: {
      type: Array,
      default: null,
    },
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
    isLoading: {
      type: Boolean,
      required: true,
    },
    showSelect: {
      type: Boolean,
      default: false,
    },
    simpleFilterEnum: {
      type: String,
      default: () => SIMPLE_FILTER.NONE,
    },
    disablePagination: {
      type: Boolean,
      default: false,
    },
    disableHeaders: {
      type: Boolean,
      default: false,
    },
    twoColumnLayout: {
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
      activeIndex: null,
      activeIndexId: null,
      selected: [],
      searchText: '',
      options: {},
      rowItemsPerPage: [10, 20, 50, 100, 500],
      visibleHeaders: [],
      initialsFetched: false,
    }
  },
  created() {
    this.options = {
      sortBy: this.sorter ? [this.sorter[0].column] : [],
      sortDesc: this.sorter ? [this.sorter[0].direction === DIRECTION.DESCENDING] : [],
      page: this.paging ? this.paging.page : 1,
      itemsPerPage: this.paging ? this.paging.itemsPerPage : 10,
    }
  },
  computed: {
    items() {
      return this.fixedItems ? this.fixedItems : this.$store.state[this.namespace].items
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
  methods: {
    async fetchGrid(search, params, filter, paging, sorter) {
      let finalFilter = null

      if (this.fixedFilter && filter) {
        finalFilter = [].concat(this.fixedFilter, filter)
      } else if (filter) {
        finalFilter = [].concat(filter)
      } else if (this.fixedFilter) {
        finalFilter = [].concat(this.fixedFilter)
      }

      await this.$store.dispatch(withNamespace(this.namespace, GRID.ACTIONS.FETCH_WITH_DATA), {
        search: search || null,
        namespace: this.namespace,
        filter: finalFilter || null,
        paging: paging || null,
        sorter: sorter || null,
        params: this.fixedParams,
      })
    },
    async fetchGridWithInitials(search, params, filter, paging, sorter) {
      let finalFilter = null

      if (this.fixedFilter && filter) {
        finalFilter = [].concat(this.fixedFilter, filter)
      } else if (filter) {
        finalFilter = [].concat(filter)
      } else if (this.fixedFilter) {
        finalFilter = [].concat(this.fixedFilter)
      }

      await this.$store.dispatch(withNamespace(this.namespace, GRID.ACTIONS.FETCH_WITH_INITIAL_STATE), {
        search: search || '',
        namespace: this.namespace,
        filter: finalFilter || null,
        paging: paging || null,
        sorter: sorter || null,
        params: this.fixedParams,
      })

      this.initialsFetched = true
    },
    onRowClicked(props) {
      if (this.showActiveRow) {
        this.activeIndex = props.index
        this.activeIndexId = props.item.id
      }
      this.expandClick ? props.expand(!props.isExpanded) : null
      this.returnRowProps ? this.$emit('row-props', props) : null
    },
    getVisibleHeaders(headers) {
      if (!headers) {
        return
      }
      const truncatedHeaders = headers.map((header) => {
        let truncatedHeader = { ...header }

        if (truncatedHeader.class) {
          truncatedHeader.class = `${header.class} truncate`
        } else {
          truncatedHeader.class = 'truncate'
        }
        return truncatedHeader
      })
      return truncatedHeaders.filter((item) => item.visible === true || item.alwaysVisible === true)
    },
    isItemVisible(name) {
      const index = this.visibleHeaders.findIndex((item) => item.value === name)
      return index !== -1
    },
    clearSelected() {
      this.selected = []
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
        if (this.initialsFetched) {
          this.fetchGrid(this.search, this.fixedParams, this.filter, paging, sorter)
        }
      },
      deep: true,
    },
    selected(selected) {
      this.$emit('input', selected)
    },
    headers: {
      immediate: true,
      handler(headers) {
        this.visibleHeaders = this.getVisibleHeaders(headers)
      },
    },
  },
  async beforeDestroy() {
    await this.$store.dispatch(withNamespace(this.namespace, GRID.ACTIONS.RESET))
  },
}
</script>

<style lang="sass">
.primary-row > td:not(:first-child)
  text-align: left
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
