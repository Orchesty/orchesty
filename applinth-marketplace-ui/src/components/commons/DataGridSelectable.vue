<template>
  <div>
    <div class="d-flex align-center justify-space-between mb-2 ml-4">
      <label class="d-flex align-center mr-4" @click="onSelectAll">
        <v-simple-checkbox
          :indeterminate="!areAllSelected && isSomethingSelected"
          :value="isSomethingSelected"
          @input="onSelectAll"
        />
        <span class="text--secondary">{{ $t("trashPage.selectAll") }}</span>
      </label>
      <div class="d-flex align-center text--secondary">
        <span v-if="$slots.actions" class="mr-2">
          {{ $t("trashPage.selectionActions") }}:
        </span>
        <slot name="actions" />
      </div>
    </div>
    <v-card outlined class="custom-table">
      <v-data-table
        v-model="selectedItems"
        :items="items"
        :headers="headers"
        :options.sync="options"
        :footer-props="{
          'items-per-page-options': [5, 10, 20],
        }"
        show-select
        item-key="id"
        :loading="isLoading"
        :loading-text="$t('grid.state.loading')"
        :server-items-length="total"
        :sort-by="sortBy"
        :sort-desc="sortDesc"
      >
        <template #[`header.data-table-select`]>
          <!-- Intentionally nothing - replaced by independent checkbox -->
        </template>
        <template
          v-for="item in headers"
          #[`header.${item.value}`]="{ header }"
        >
          <span :key="item.value" class="text-capitalize font-weight-bold">
            {{ header.text }}
          </span>
        </template>
        <template #item="props">
          <tr
            :key="props.index"
            :class="
              activeIndex === props.index && activeIndexId === props.item.id
                ? 'selected-row'
                : ''
            "
            class="applinth-pointer"
          >
            <td>
              <v-checkbox
                v-model="props.isSelected"
                class="ma-auto pt-0 z-10"
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
            <slot :items="props" :expanded="props.isExpanded" />
          </tr>
        </template>
      </v-data-table>
    </v-card>
  </div>
</template>

<script>
import { callApi } from "@/utils/apiFetch"

export default {
  name: "DataGridSelectable",
  props: {
    headers: {
      type: Array,
      required: true,
    },
    gridSettings: {
      type: Object,
      required: true,
    },
    isSelectable: {
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
  },
  data() {
    return {
      options: {},
      items: [],
      selectedItems: [],
      activeIndex: null,
      activeIndexId: null,
      isLoading: false,
      total: 0,
    }
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
    onRowClicked(props) {
      this.activeIndex = props.index
      this.activeIndexId = props.item.id
    },
    onSelectAll() {
      if (this.areAllSelected) {
        this.deselectAll()
      } else {
        this.selectAll()
      }
    },
    selectAll() {
      this.selectedItems = [...this.items]
    },
    deselectAll() {
      this.selectedItems = []
    },
  },
  watch: {
    selectedItems(selectedItems) {
      this.$emit("select", selectedItems)
    },
    options: {
      deep: true,
      handler() {
        this.gridFetch()
      },
    },
  },
  computed: {
    areAllSelected() {
      function equalSets(as, bs) {
        if (as.size !== bs.size) {
          return false
        }
        for (const a of as) {
          if (!bs.has(a)) {
            return false
          }
        }
        return true
      }

      if (equalSets(new Set(this.items), new Set(this.selectedItems))) {
        return true
      }
      return false
    },
    isSomethingSelected() {
      return this.selectedItems.length > 0
    },
  },
  mounted() {
    this.gridFetch()
  },
}
</script>

<style lang="scss" scoped>
.custom-table ::v-deep {
  .selected-row {
    background: var(--v-gray-base) !important;
  }
}
</style>
