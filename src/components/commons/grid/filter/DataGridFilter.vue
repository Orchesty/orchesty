<template>
  <v-row v-if="quickFilters.length > 0" dense>
    <v-col cols="12">
      <quick-grid-filter
        ref="quickGridFilter"
        :quick-filters="quickFilters"
        :filter="filter"
        :filter-meta="filterMeta"
        :on-change="onChangeFilter"
        :is-loading="isLoading"
      >
        <template v-if="!simpleFilter" #resetClearButtons="{}">
          <v-btn color="primary" icon @click="reload">
            <v-icon> mdi-reload </v-icon>
          </v-btn>
          <!--          <v-btn color="primary" icon @click="clear(onClearButton)">-->
          <!--            <v-icon> mdi-close </v-icon>-->
          <!--          </v-btn>-->
        </template>
        <template #headers>
          <slot name="headers"></slot>
        </template>
      </quick-grid-filter>

      <simple-grid-filter
        v-if="simpleFilter"
        ref="simpleGridFilter"
        :key="key"
        :simple-filter-enum="simpleFilterEnum"
        :headers="headers"
        :filter="filter"
        :filter-meta="filterMeta"
        :on-change="onChangeFilter"
        @sendFilter="sendFilter"
      >
        <template #resetClearButtons="{ onClearButton }">
          <v-btn class="ml-2" color="primary" icon @click="reload">
            <v-icon> mdi-reload </v-icon>
          </v-btn>
          <v-btn class="ml-1" color="primary" icon @click="clear(onClearButton)">
            <v-icon> mdi-close </v-icon>
          </v-btn>
        </template>
      </simple-grid-filter>
    </v-col>
  </v-row>
</template>

<script>
import QuickGridFilter from './QuickGridFilter'
import SimpleGridFilter from './SimpleGridFilter'
import { FILTER } from '@/services/enums/gridEnums'
import { GRID } from '@/store/modules/grid/types'
import { mapActions } from 'vuex'
import { DATA_GRIDS } from '@/services/enums/dataGridEnums'
import { SIMPLE_FILTER } from '@/services/enums/dataGridFilterEnums'

export default {
  name: 'DataGridFilter',
  components: { QuickGridFilter, SimpleGridFilter },
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
    onReload: {
      type: Function,
      required: true,
    },
    onClear: {
      type: Function,
      required: true,
    },
    headers: {
      type: Array,
      required: true,
    },
    isLoading: {
      type: Boolean,
      required: true,
    },
    simpleFilterEnum: {
      type: String,
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
      currentFilter: [],
      currentMeta: {},
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
  },
  methods: {
    ...mapActions(DATA_GRIDS.USER_TASK, [GRID.ACTIONS.GRID_FETCH]),
    onChangeFilter(filter, meta) {
      this.currentFilter = filter
      this.currentMeta = meta

      if (meta.type === FILTER.QUICK_FILTER) this.sendFilter()
    },
    clear() {
      this.$refs.quickGridFilter.onClear()
      if (this.filter.length === 0) {
        this.key++
      }

      this.$refs.simpleGridFilter.logsFilterValues.fullTextSearch = null
      this.$refs.simpleGridFilter.logsFilterValues.timeMargin = 0
      this.currentFilter = []
      this.currentMeta = {}
      this.onClear()
    },
    async reload() {
      await this.onReload()
    },
    async sendFilter() {
      if (SIMPLE_FILTER.LOGS === this.simpleFilterEnum) {
        this.onFilter(
          this.$refs.simpleGridFilter.logsFilterValues.fullTextSearch,
          null,
          [].concat(this.currentFilter, this.$refs.simpleGridFilter.logsFilter),
          null,
          null
        )
      } else if (SIMPLE_FILTER.TRASH === this.simpleFilterEnum) {
        this.onFilter(null, null, [].concat(this.currentFilter, this.$refs.simpleGridFilter.trashFilter), null, null)
      } else {
        this.onFilter(null, null, this.currentFilter, null, null)
      }
    },
  },
}
</script>
