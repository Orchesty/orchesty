<template>
  <v-card outlined>
    <v-data-table
      :items="items"
      :headers="headers"
      :options.sync="options"
      :footer-props="{
        'items-per-page-options': [5, 10, 20],
      }"
      :loading="isLoading"
      :loading-text="$t('grid.state.loading')"
    >
      <template v-for="item in headers" #[`header.${item.value}`]="{ header }">
        <span :key="item.value" class="text-capitalize font-weight-bold">
          {{ $t(header.text) }}
        </span>
      </template>
      <template #item="props">
        <tr :key="props.index">
          <slot :items="props" :expanded="props.isExpanded" />
        </tr>
      </template>
    </v-data-table>
  </v-card>
</template>

<script>
import { callApi } from '@/utils/apiFetch'

export default {
  name: 'DataGrid',
  props: {
    headers: {
      type: Array,
      required: true,
    },
    gridSettings: {
      type: Object,
      required: true,
    },
    fetchOnInit: {
      type: Boolean,
      default: false,
    },
  },
  data() {
    return {
      options: {},
      items: [],
      isLoading: true,
    }
  },
  methods: {
    optionsParser(data) {
      return {
        sortBy: data.sorter.length ? [data.sorter[0].column] : [],
        sortDesc: data.sorter.length
          ? [data.sorter[0].direction === 'DESC']
          : [],
        page: data.paging ? data.paging.page : 1,
        itemsPerPage: data.paging ? data.paging.itemsPerPage : 10,
      }
    },
    async gridFetch(params) {
      this.isLoading = true
      const gridResponseData = await callApi({
        requestData: this.gridSettings,
        params: params,
      })
      this.items = gridResponseData.items
      this.options = this.optionsParser(gridResponseData)
      this.isLoading = false
    },
  },
  async mounted() {
    if (this.fetchOnInit) {
      await this.gridFetch()
    }
  },
}
</script>

<style lang="scss">
.v-data-table-header {
  background: var(--v-primary-base) !important;
  th {
    color: var(--v-white-base) !important;
    .theme--light.v-icon {
      color: var(--v-white-base) !important;
    }
  }
}
</style>
