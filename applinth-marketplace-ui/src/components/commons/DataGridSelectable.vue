<template>
  <div>
    <div class="d-flex align-center justify-space-between mb-2 ml-4">
      <label class="d-flex align-center mr-4" @click="onSelectAll">
        <v-simple-checkbox
          :indeterminate="!areAllSelected && isSomethingSelected"
          :value="isSomethingSelected"
          @input="onSelectAll"
        />
        <span class="text--secondary">{{ $t('trashPage.selectAll') }}</span>
      </label>
      <div class="d-flex align-center text--secondary">
        <span v-if="$slots.actions" class="mr-2">
          {{ $t('trashPage.selectionActions') }}:
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
        :sort-desc="sortDesc"
        :sort-by="sortBy"
      >
        <template #[`header.data-table-select`]>
          <!-- Intentionally nothing - replaced by independent checkbox -->
        </template>
        <template
          v-for="item in headers"
          #[`header.${item.value}`]="{ header }"
        >
          <span :key="item.value" class="text-capitalize font-weight-bold">
            {{ $t(header.text) }}
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
import { callApi } from '@/utils/apiFetch'

export default {
  name: 'DataGridSelectable',
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
    isSelectable: {
      type: Boolean,
      default: false,
    },
    sortDesc: {
      type: Boolean,
      default: false,
    },
    sortBy: {
      type: [String, null],
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
    }
  },
  methods: {
    optionsParser(data) {
      return {
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
      this.$emit('select', selectedItems)
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
  async mounted() {
    if (this.fetchOnInit) {
      await this.gridFetch()
    }
  },
}
</script>

<style lang="scss" scoped>
.custom-table ::v-deep {
  .v-data-table-header {
    background: var(--v-primary-base) !important;

    th {
      color: var(--v-white-base) !important;

      .theme--light.v-icon {
        color: var(--v-white-base) !important;
      }
    }
  }

  .selected-row {
    background: var(--v-gray-base) !important;
  }
}
</style>
