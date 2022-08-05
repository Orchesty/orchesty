<template>
  <v-container fluid class="pb-0 px-0">
    <v-row dense v-if="title" class="mb-6">
      <v-col cols="12">
        <h1>{{ title }}</h1>
      </v-col>
    </v-row>
    <div :class="{ 'mb-6': hasTableActions }">
      <v-row
        :class="{ 'mb-6': $slots['advancedActions'] }"
        dense
        v-if="$slots['actions']"
      >
        <v-col cols="12">
          <slot name="actions" />
        </v-col>
      </v-row>
      <v-row dense v-if="$slots['advancedActions']">
        <v-col cols="12">
          <slot name="advancedActions" />
        </v-col>
      </v-row>
      <v-row dense v-if="useSearch || useQuickFilter">
        <v-col cols="12">
          <SearchFilter v-if="useSearch" :table-options="tableOptions" />
        </v-col>
        <v-col cols="12">
          <QuickFilters
            v-if="useQuickFilter"
            :table-options="tableOptions"
            @filter="callFilter"
          />
        </v-col>
      </v-row>
    </div>
    <v-row dense v-if="useAdvancedFilter">
      <v-expand-transition>
        <v-col cols="12">
          <AdvancedFilters :table-options="tableOptions" @filter="callFilter" />
        </v-col>
      </v-expand-transition>
    </v-row>
  </v-container>
</template>

<script lang="ts">
import { Component, Prop, Vue } from "vue-property-decorator";
import QuickFilters from "./partials/QuickFilters.vue";
import AdvancedFilters from "./partials/AdvancedFilters.vue";
import Button from "../inputsAndControls/Button.vue";
import { TableFilter, TableFilterPayload, TableOptions } from "./types";
import TextField from "../inputsAndControls/TextField.vue";
import store from "../../../store";
import { TablesActions } from "../../../store/modules/tables";
import SearchFilter from "./partials/SearchFilter.vue";

@Component({
  components: {
    QuickFilters,
    AdvancedFilters,
    Button,
    TextField,
    SearchFilter,
  },
})
export default class TableHeader extends Vue {
  @Prop({ required: true, type: Object })
  private tableOptions!: TableOptions;

  @Prop({ required: false, type: Boolean, default: true })
  private pt!: boolean;

  @Prop({ type: Boolean, default: false })
  private useSearch!: boolean;

  @Prop({ type: String, default: "" })
  private title!: string;

  @Prop({ type: Boolean, default: false })
  private useQuickFilter!: boolean;

  @Prop({ type: Boolean, default: false })
  private useAdvancedFilter!: boolean;

  private callFilter(filter: TableFilter): void {
    store.dispatch(`${this.tableOptions.namespace}/${TablesActions.Filter}`, {
      namespace: this.tableOptions.namespace,
      filter,
    } as TableFilterPayload);
  }

  get hasTableActions() {
    return this.useSearch || this.useQuickFilter || !!this.$slots["actions"];
  }
}
</script>

<style lang="scss" scoped></style>
