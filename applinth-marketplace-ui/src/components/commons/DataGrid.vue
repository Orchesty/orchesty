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
      :server-items-length="total"
      :sort-by="sortBy"
      :sort-desc="sortDesc"
      :show-expand="showExpand"
      :item-key="itemKey"
    >
      <template v-for="item in headers" #[`header.${item.value}`]="{ header }">
        <span :key="item.value" class="text-capitalize font-weight-bold">
          {{ header.text }}
        </span>
      </template>
      <template #item="props">
        <tr :key="props.index">
          <td
            v-if="showExpand"
            :style="props.isExpanded ? 'border-bottom: none' : ''"
          >
            <v-icon
              @click.stop=""
              @click="() => props.expand(!props.isExpanded)"
            >
              {{ props.isExpanded ? "mdi-chevron-up" : "mdi-chevron-down" }}
            </v-icon>
          </td>
          <slot :items="props" :expanded="props.isExpanded" />
        </tr>
      </template>
      <template #expanded-item="expandedProps">
        <td
          v-if="showExpand"
          :colspan="expandedColspan"
          class="py-2 px-5 expanded-background expanded-row border-bottom"
        >
          <slot name="expand" :items="expandedProps" />
        </td>
      </template>
    </v-data-table>
  </v-card>
</template>

<script>
import { callApi } from "@/utils/apiFetch"

export default {
  name: "DataGrid",
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
    sortBy: {
      type: Array,
      default: null,
    },
    sortDesc: {
      type: Array,
      default: null,
    },
    showExpand: {
      type: Boolean,
      default: false,
    },
    itemKey: {
      type: String,
      required: true,
    },
  },
  data() {
    return {
      options: {},
      items: [],
      isLoading: true,
      total: 0,
    }
  },
  computed: {
    expandedColspan() {
      let colspan = this.headers.length

      if (this.showExpand) {
        colspan++
      }

      return colspan
    },
  },
  methods: {
    async gridFetch() {
      function optionsToRequestParams(options) {
        const { page, itemsPerPage, sortBy, sortDesc } = options
        const paging = { page, itemsPerPage }
        const sorter =
          sortBy?.map((column, index) => {
            return { column, direction: sortDesc[index] ? "DESC" : "ASC" }
          }) ?? []
        return {
          paging,
          sorter,
        }
      }

      function responseDataToTotal(data) {
        return data.paging?.total ?? 0
      }

      this.isLoading = true
      const data = await callApi({
        requestData: this.gridSettings,
        params: optionsToRequestParams(this.options),
      })
      this.items = data.items
      this.total = responseDataToTotal(data)
      this.isLoading = false
    },
  },
  watch: {
    options: {
      deep: true,
      handler() {
        this.gridFetch()
      },
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

.border-bottom {
  border-bottom: thin solid rgba(0, 0, 0, 0.12);
}
</style>
