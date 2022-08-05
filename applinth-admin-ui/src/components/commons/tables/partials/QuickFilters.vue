<template>
  <div v-if="quickFilters.length" class="quick-filters">
    <span class="quick-filters__title font-weight-bold"
      >{{ $t("table.quickFilters") }}:
    </span>
    <div class="quick-filters__items">
      <template v-for="filter in quickFilters">
        <ChipButton
          @click="() => handleClick(filter)"
          :active="filter.id === activeQuickFilter"
          :key="filter.id"
          >{{ filter.name }}</ChipButton
        >
      </template>
    </div>
    <div class="quick-filters__edit">
      <RoundButton @click="handleUpdateFilters" icon="pencil" />
      <UpdateSavedFiltersModal :table-options="tableOptions" />
    </div>
  </div>
</template>

<script lang="ts">
import { Component, Prop, Vue } from "vue-property-decorator";
import { EventBus } from "../../../../enums";
import { QuickFilter, TableLastState, TableSettings } from "../../../../types";
import { userSettings } from "../../../../utils/userSettings";
import { eventBus } from "../../../../utils/eventBus";
import ChipButton from "../../../commons/inputsAndControls/ChipButton.vue";
import RoundButton from "../../../commons/inputsAndControls/RoundButton.vue";
import { TableFilter, TableOptions } from "../types";
import UpdateSavedFiltersModal from "./UpdateSavedFiltersModal.vue";
import store from "../../../../store";
import { TablesActions } from "../../../../store/modules/tables";

@Component({
  components: {
    ChipButton,
    RoundButton,
    UpdateSavedFiltersModal,
  },
})
export default class QuickFilters extends Vue {
  @Prop({ required: true, type: Object })
  private tableOptions!: TableOptions;

  private activatedQuickFilter = 0;

  get quickFilters(): TableSettings["quickFilters"] {
    return userSettings.getTableQuickFilters(this.tableOptions.namespace);
  }

  get activeQuickFilter(): TableLastState["activeQuickFilter"] {
    if (this.tableOptions.saveTableState) {
      return userSettings.getTableLastState(this.tableOptions.namespace)
        .activeQuickFilter;
    }

    return this.activatedQuickFilter;
  }

  private handleClick(filter: QuickFilter): void {
    if (filter.id === this.activeQuickFilter) {
      this.handleSaveUserSettings(null, null);
      eventBus.$emit(
        `${EventBus.OnTableQuickFilter}/${this.tableOptions.namespace}`,
        null
      );
      this.$emit("filter", null);
    } else {
      this.handleSaveUserSettings(filter.filter, filter.id);
      eventBus.$emit(
        `${EventBus.OnTableQuickFilter}/${this.tableOptions.namespace}`,
        filter.filter
      );
      this.$emit("filter", filter.filter);
    }
  }

  private handleUpdateFilters(): void {
    eventBus.$emit(
      `${EventBus.UpdateSavedFiltersModal}/${this.tableOptions.namespace}`,
      this.quickFilters
    );
  }

  private handleSaveUserSettings(
    filter: TableFilter | null,
    activeQuickFilter: TableLastState["activeQuickFilter"]
  ): void {
    this.activatedQuickFilter = activeQuickFilter || 0;

    if (this.tableOptions.saveTableState) {
      const lastState = userSettings.getTableLastState(
        this.tableOptions.namespace
      );
      const pager = { page: 1, size: lastState.pager?.size ?? 10 };
      userSettings.updateTableLastState(this.tableOptions.namespace, {
        ...lastState,
        activeQuickFilter,
        filter: filter,
        pager: pager,
      });
    }

    // Change paging when is filter changed
    store.dispatch(
      `${this.tableOptions.namespace}/${TablesActions.ResetPaging}`
    );
  }
}
</script>

<style lang="scss" scoped>
.quick-filters {
  display: flex;
  align-items: baseline;

  & > *:not(:last-child) {
    margin-right: 0.5rem;
  }

  &__title {
    white-space: nowrap;
  }

  &__items {
    display: flex;
    flex-wrap: wrap;

    & > *:not(:last-child) {
      margin-right: 0.2rem;
    }
  }
}
</style>
