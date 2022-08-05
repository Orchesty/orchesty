<template>
  <div>
    <v-row dense>
      <v-col cols="auto" class="d-flex flex-wrap align-center flex-grow-1">
        <app-button
          v-for="(item, index) in items"
          :key="index"
          :height="30"
          :min-width="132"
          :disabled="isLoading"
          :outlined="!item.active"
          :color="item.active ? 'primary' : 'secondary'"
          class="mr-2 body-2 font-weight-medium"
          :button-title="$t(item.name)"
          :on-click="() => onChangeFilter(index, item)"
        />
        <slot name="resetClearButtons" :on-clear-button="() => {}" />
        <slot name="advancedFilter" />
      </v-col>
    </v-row>
  </div>
</template>

<script>
import { FILTER } from '@/services/enums/gridEnums'
import moment from 'moment'
import AppButton from '@/components/commons/button/AppButton'

export default {
  name: 'QuickGridFilter',
  components: { AppButton },
  props: {
    isViewer: {
      type: Boolean,
      default: false,
    },
    quickFilters: {
      type: Array,
      required: true,
    },
    isLoading: {
      type: Boolean,
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
    onChange: {
      type: Function,
      required: true,
    },
  },
  data() {
    return {
      items: this.createItems(this.quickFilters, this.filterMeta.index || undefined),
    }
  },
  watch: {
    items: {
      deep: true,
      handler(val) {
        if (this.isViewer) {
          if (!this.items.length) {
            return
          }
          if (!val) {
            return
          }
          let filteredVal = this.filter.filter((filter) => {
            return filter.some((filter1) => {
              return Object.hasOwnProperty.call(filter1, 'isQuickFilter')
            })
          })
          if (!filteredVal.length) {
            return
          }

          let date1 = moment(filteredVal[0][0].value[0])
          let date2 = moment(filteredVal[0][0].value[1])
          var diff = date2.diff(date1, 'hours')
          if (diff === 1) {
            this.items[2].active = true
          }
        }
      },
    },
    filter: {
      handler(val) {
        if (!this.items.length) {
          return
        }
        if (!val) {
          return
        }
        let filteredVal = val.filter((filter) => {
          return filter.some((filter1) => {
            return Object.hasOwnProperty.call(filter1, 'isQuickFilter')
          })
        })
        if (!filteredVal.length) {
          return
        }

        let date1 = moment(filteredVal[0][0].value[0])
        let date2 = moment(filteredVal[0][0].value[1])
        var diff = date2.diff(date1, 'hours')
        if (diff === 1) {
          this.items[2].active = true
        }
      },
    },
    filterMeta(meta) {
      if (!meta || meta.type !== FILTER.QUICK_FILTER) {
        this.items = this.createItems(this.quickFilters, meta.index)
      }
    },
    quickFilters(quickFilters) {
      this.items = this.createItems(quickFilters, undefined)
    },
  },
  created() {
    if (this.quickFilters.length && this.quickFilters[this.filterMeta.index]) {
      this.onChange(this.quickFilters[this.filterMeta.index].filter, {
        type: FILTER.QUICK_FILTER,
        index: this.filterMeta.index,
      })
    }
  },
  // mounted() {
  //   this.onChangeFilter(2, this.items[2])
  // },
  methods: {
    onClear() {
      this.items = this.createItems(this.quickFilters, undefined)
    },
    onChangeFilter(index, filter) {
      this.items = this.createItems(this.items, index)
      let withDefault = filter.filter

      this.onChange(withDefault, { type: FILTER.QUICK_FILTER, index })
    },
    createItems(filters, index = undefined) {
      return filters.map((item, i) => {
        item.active = false

        if (index === i) {
          item.active = true
        }

        return item
      })
    },
  },
}
</script>
<style scoped>
.v-enter-active,
.v-leave-active {
  transition: opacity 0.5s ease;
}

.v-enter-from,
.v-leave-to {
  opacity: 0;
}
</style>
