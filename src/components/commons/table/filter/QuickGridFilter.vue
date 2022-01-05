<template>
  <v-row>
    <v-col cols="auto" class="d-flex flex-wrap align-center flex-grow-1">
      <v-btn
        v-for="(item, index) in items"
        :key="index"
        small
        :outlined="!item.active"
        :color="item.active ? 'primary' : 'secondary'"
        class="mr-2"
        @click="onChangeFilter(index, item)"
      >
        {{ $t(item.name) }}
      </v-btn>
      <slot name="advancedFilter" />
      <slot name="buttonLeft" />
      <slot name="buttonRight" :on-clear-button="onClear" />
    </v-col>
  </v-row>
</template>

<script>
import { FILTER } from '@/services/enums/gridEnums'

export default {
  name: 'QuickGridFilter',
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
