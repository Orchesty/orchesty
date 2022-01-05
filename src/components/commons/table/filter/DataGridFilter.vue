<template>
  <v-row v-if="quickFilters.length > 0 || !disableAdvancedFilter">
    <v-col cols="12" class="pb-0">
      <quick-grid-filter
        :quick-filters="quickFilters"
        :filter="filter"
        :filter-meta="filterMeta"
        :on-change="onChangeFilter"
        :default-setting="defaultSetting"
      >
        <template v-if="!disableAdvancedFilter" slot="advancedFilter">
          <v-btn icon color="primary" @click="filterToggle">
            <v-badge :value="filterCount" :content="filterCount" offset-y="20">
              <v-icon> mdi-filter-variant </v-icon>
            </v-badge>
          </v-btn>
        </template>
        <template slot="buttonLeft">
          <v-btn color="primary" icon @click="reload">
            <v-icon> mdi-reload </v-icon>
          </v-btn>
        </template>
        <template slot="buttonRight">
          <v-btn text class="ml-auto" @click="clear">
            {{ $t('dataGrid.clear') }}
          </v-btn>
        </template>
        <template #headers>
          <slot name="headers"></slot>
        </template>
        <hide-header v-if="!disableHideHeaders" :headers="headers" :on-columns-change="onColumnsChangeInternal" />
      </quick-grid-filter>

      <simple-grid-filter
        v-if="simpleFilter"
        ref="simpleGridFilter"
        :key="key"
        :value="fullTextSearchProp"
        :show-full-text-search="showFullTextSearch"
        :headers="headers"
        :filter="filter"
        :filter-meta="filterMeta"
        :on-change="onChangeFilter"
        @input="onFullTextSearch"
        @sendFilter="sendFilter"
      />
    </v-col>
  </v-row>
</template>

<script>
import QuickGridFilter from './QuickGridFilter'
import SimpleGridFilter from './SimpleGridFilter'
import { FILTER, OPERATOR } from '../../../../store/grid'
import HideHeader from '@/components/commons/table/HideHeader'
import { GRID } from '@/store/grid/store/types'
import { mapActions } from 'vuex'
import { DATA_GRIDS } from '@/store/grid/grids'
import { withNamespace } from '@/store/utils'

export default {
  name: 'DataGridFilter',
  components: { HideHeader, QuickGridFilter, SimpleGridFilter },
  props: {
    quickFilters: {
      type: Array,
      required: true,
    },
    filter: {
      type: Array,
      required: true,
    },
    filterMeta: {
      type: Object,
      required: true,
    },
    onFilter: {
      type: Function,
      required: true,
    },
    onClear: {
      type: Function,
      required: true,
    },
    onSave: {
      type: Function,
      required: true,
    },
    headers: {
      type: Array,
      required: true,
    },
    showFullTextSearch: {
      type: Boolean,
      required: true,
    },
    disableAdvancedFilter: {
      type: Boolean,
      default: false,
    },
    defaultSetting: {
      type: Object,
      required: false,
      default: () => ({}),
    },
    onColumnsChangeInternal: {
      type: Function,
      required: true,
    },
    disableHideHeaders: {
      type: Boolean,
      required: true,
    },
    params: {
      type: Object,
      required: true,
    },
    simpleFilter: {
      type: Boolean,
      default: false,
    },
  },
  data() {
    return {
      FILTER,
      expandedFilter: [],
      isAdvanced: false,
      currentFilter: [],
      currentMeta: {},
      fullTextSearch: '',
      timeMargin: '',
      timeMarginFilter: [{ column: 'time_margin', operator: OPERATOR.EQUAL, value: '' }],
      key: 0,
    }
  },
  computed: {
    filterCount() {
      let i = 0
      this.filter.forEach((and) => {
        and.forEach(() => {
          let defaultFilter = false
          and.forEach((item) => {
            item.default ? (defaultFilter = true) : (defaultFilter = false)
          })
          if (!defaultFilter) {
            i++
          }
        })
      })

      return i
    },
    fullTextSearchProp() {
      return { fullTextSearch: this.fullTextSearch, timeMargin: this.timeMarginFilter[0].value }
    },
  },
  methods: {
    ...mapActions(DATA_GRIDS.USER_TASK, [GRID.ACTIONS.GRID_FETCH]),
    onFullTextSearch(val) {
      this.fullTextSearch = val?.fullTextSearch
      this.timeMarginFilter[0].value = val?.timeMargin
    },
    filterToggle() {
      this.expandedFilter = this.expandedFilter.length ? [] : [0]
    },
    onChangeFilter(filter, meta) {
      this.currentFilter = filter
      this.currentMeta = meta

      if (meta.type === FILTER.QUICK_FILTER) this.sendFilter()
    },
    save() {
      // this.onSave()
      this.$refs.simpleGridFilter.save()
    },
    clear() {
      if (this.filter.length === 0) {
        this.key++
      }
      this.currentFilter = []
      this.currentMeta = {}
      this.fullTextSearch = ''
      this.timeMarginFilter[0].value = ''
      this.onClear()
    },
    async reload() {
      await this.$store.dispatch(withNamespace(this.params.namespace, GRID.ACTIONS.GRID_FILTER), {
        ...this.params,
      })
    },
    async sendFilter() {
      if (this.fullTextSearch) {
        this.onFilter(this.currentFilter, this.currentMeta, {
          fullTextSearch: this.fullTextSearch,
          timeMargin: this.timeMarginFilter,
        })
      } else {
        this.onFilter(this.currentFilter, this.currentMeta)
      }
    },
  },
}
</script>
