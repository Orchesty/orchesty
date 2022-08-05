<template>
  <div>
    <base-button
      v-for="(filter, index) in filters"
      :key="index"
      :on-click="() => selectFilter(filter, index)"
      :color="isActive(index) ? 'primary' : 'secondary'"
      :outlined="!isActive(index)"
      :button-title="filterNames[index]"
      :height="30"
      :min-width="132"
      custom-class="body-2 font-weight-medium quick-filter"
    />
  </div>
</template>

<script>
import BaseButton from '@/components/commons/BaseButton'
import { OPERATOR } from '@/enums/filterEnums'
import moment from 'moment'
export default {
  name: 'DataGridQuickFilter',
  components: { BaseButton },
  props: {
    column: {
      type: String,
      required: true,
    },
  },
  data() {
    return {
      selectedFilter: null,
      selectedFilterIndex: null,
      filters: null,
      filterDurations: [
        { amount: 5, unit: 'minutes' },
        { amount: 30, unit: 'minutes' },
        { amount: 1, unit: 'hour' },
        { amount: 6, unit: 'hour' },
        { amount: 1, unit: 'day' },
      ],
      filterNames: [
        this.$t('quickFilter.fiveMinutes'),
        this.$t('quickFilter.thirtyMinutes'),
        this.$t('quickFilter.hour'),
        this.$t('quickFilter.sixHours'),
        this.$t('quickFilter.day'),
      ],
    }
  },
  methods: {
    isActive(index) {
      return index === this.selectedFilterIndex
    },
    filterCreate(value) {
      return {
        column: this.column,
        operator: OPERATOR.BETWEEN,
        values: value,
      }
    },
    filterRangeCreate(rangeData) {
      return [
        moment().utc().subtract(rangeData.amount, rangeData.unit).format(),
        moment().utc().format(),
      ]
    },
    initFilters() {
      const filters = []
      this.filterDurations.forEach((filterDuration) => {
        filters.push(this.filterCreate(this.filterRangeCreate(filterDuration)))
      })
      this.filters = filters
    },
    selectFilter(filter, index) {
      if (this.selectedFilterIndex === index) {
        this.selectedFilter = null
        this.selectedFilterIndex = null
      } else {
        this.selectedFilter = filter
        this.selectedFilterIndex = index
      }
    },
  },
  mounted() {
    this.initFilters()
  },
  watch: {
    selectedFilter: {
      deep: true,
      handler(filter) {
        this.$emit('filterChanged', filter)
      },
    },
  },
}
</script>

<style scoped>
.quick-filter:not(:last-child) {
  margin-right: 10px;
}
</style>
