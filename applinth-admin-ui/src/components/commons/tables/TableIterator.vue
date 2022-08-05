<template>
  <v-data-iterator
    :loading="loading"
    :headers="tableOptions.headers"
    :items="tableState.items"
    :items-per-page="tableOptions.defaultPerPage"
    :no-data-text="$t('table.noData')"
    :options.sync="options"
    :page.sync="tableState.pager.page"
    :footer-props="{
      'items-per-page-options': itemsPerPage,
      'items-per-page-text': $t(
        tableOptions.itemPerPageText
          ? tableOptions.itemPerPageText
          : 'table.itemsPerPage'
      ),
      'page-text': $t(
        tableOptions.pageText ? tableOptions.pageText : 'table.numberOfPages',
        [tableState.pager.page, tableState.pager.last]
      ),
    }"
    :server-items-length="tableState.pager.total"
    :single-select="singleSelect"
    v-model="selected"
  >
    <template v-slot:default="props">
      <slot name="default" v-bind="props" :value="props" />
    </template>
  </v-data-iterator>
</template>

<script lang="ts">
import { Component, Prop, Watch } from "vue-property-decorator";
import Table from "./Table.vue";

@Component
export default class TableIterator extends Table {
  constructor() {
    super();
    this.hideLoading = false;
  }

  @Prop({ type: Array, default: () => [] })
  private value!: any[];

  @Prop({ type: Boolean, required: false, default: false })
  private singleSelect!: boolean;

  private selected: any[] = [];

  @Watch("selected")
  private onChangeSelected() {
    this.$emit("selected", this.selected);
    this.$emit("input", this.selected);
  }

  @Watch("value")
  onChangeValue() {
    this.selected = this.value;
  }
}
</script>

<style lang="scss" scoped>
::v-deep .sortable {
  white-space: nowrap;
}
::v-deep .text-start {
  white-space: nowrap;
}
</style>
