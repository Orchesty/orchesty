<template>
  <v-row v-if="quickFilters.length > 0" dense>
    <v-col cols="12">
      <quick-grid-filter
        v-if="!disableFilter"
        ref="quickGridFilter"
        :quick-filters="quickFilters"
        :filter="filter"
        :filter-meta="filterMeta"
        :on-change="onChangeFilter"
        :is-loading="isLoading"
        :class="{ 'mb-3': simpleFilter }"
      >
        <template v-if="!simpleFilter">
          <v-btn color="primary" icon @click="onChangeFilter">
            <v-icon> mdi-reload </v-icon>
          </v-btn>
        </template>
      </quick-grid-filter>

      <simple-grid-filter
        v-if="simpleFilter"
        :simple-filter-enum="simpleFilterEnum"
        :class="{ 'mt-2': disableFilter }"
        @onSendFilter="sendFilter"
      />
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
    disableFilter: {
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
      currentSearch: null,
      currentQuickFilter: [],
      currentMeta: {},
    }
  },
  methods: {
    ...mapActions(DATA_GRIDS.USER_TASK, [GRID.ACTIONS.GRID_FETCH]),
    onChangeFilter(filter, meta) {
      this.currentQuickFilter = filter
      this.currentMeta = meta

      if (meta.type === FILTER.QUICK_FILTER)
        this.$emit('onFetchGrid', {
          search: this.currentSearch,
          filter: [].concat(this.currentQuickFilter, this.currentFilter),
          paging: null,
          sorter: null,
        })
    },
    async sendFilter(params) {
      this.currentFilter = params.filter
      this.currentSearch = params.search

      params.filter = [].concat(this.currentFilter, this.currentQuickFilter)

      this.$emit('onFetchGrid', params)
    },
  },
}
</script>
