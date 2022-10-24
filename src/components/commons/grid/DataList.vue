<template>
  <v-card flat class="custom-card">
    <v-toolbar flat color="white">
      <v-form>
        <v-text-field
          v-model="searchText"
          :label="$t('$vuetify.searchLabel')"
          prepend-inner-icon="search"
          background-color="#F7F7F7"
          solo
          flat
          single-line
          hide-details
        />
      </v-form>
      <v-spacer />
      <slot name="toolbar" />
    </v-toolbar>

    <v-divider />

    <v-data-iterator
      :items="items"
      :options.sync="options"
      :server-items-length="totalItems"
      :loading="isLoading"
      :footer-props="{ 'items-per-page-options': rowItemsPerPage }"
    >
      <template #item="props">
        <slot :items="props" />
      </template>
    </v-data-iterator>
  </v-card>
</template>

<script>
export default {
  name: "DataList",
  props: {
    items: {
      type: Array,
      required: true,
    },
    isLoading: {
      type: Boolean,
      required: true,
    },
    paging: {
      type: Object,
      required: true,
    },
    search: {
      type: String,
      required: false,
      default: "",
    },
    onPaging: {
      type: Function,
      required: true,
    },
    onSearch: {
      type: Function,
      required: false,
      default: null,
    },
  },
  data() {
    return {
      searchText: this.search ? this.search : "",
      options: {
        page: this.paging.page,
        itemsPerPage: this.paging.itemsPerPage,
      },
      rowItemsPerPage: [10, 20, 50, 100],
    }
  },
  computed: {
    totalItems() {
      return this.paging.total
    },
  },
  watch: {
    options: {
      handler() {
        const { page, itemsPerPage } = this.options
        const paging = {
          page: page,
          itemsPerPage: itemsPerPage,
        }

        this.onPaging({ paging })
      },
      deep: true,
    },
    searchText(val) {
      this.onSearch(val)
    },
  },
}
</script>
