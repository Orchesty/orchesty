<template>
  <div class="d-flex">
    <div class="mr-2">
      <SelectBox
        :label="$t('formLabels.year')"
        :items="years"
        name="year"
        @change="onYearChange"
        :value="filter.year"
      />
    </div>
    <SelectBox
      :label="$t('formLabels.month')"
      :items="months"
      name="month"
      @change="onMonthChange"
      :value="filter.month"
      :disabled="filter.year === VALUE_ALL"
    />
  </div>
</template>

<script lang="ts">
import { Vue, Component, Prop } from "vue-property-decorator"
import SelectBox from "@/components/commons/inputsAndControls/SelectBox.vue"

export type HistoryFilterType = {
  year: number | "all"
  month: number | "all"
}

export const VALUE_ALL = "all"

@Component({
  components: { SelectBox },
})
export default class BillingReportsFilter extends Vue {
  @Prop({ type: Object, required: true })
  filter!: HistoryFilterType

  //todo PIP-1365 hardcoded
  months = [
    { text: this.$t("all"), value: VALUE_ALL },
    { text: "9", value: 9 },
    { text: "10", value: 10 },
    { text: "11", value: 11 },
    { text: "12", value: 12 },
  ]
  years = [
    { text: this.$t("all"), value: VALUE_ALL },
    { text: "2022", value: 2022 },
  ]

  private onMonthChange(value: number | "all") {
    if (value && this.filter.year === VALUE_ALL) {
      return
    }

    this.$emit("change", { ...this.filter, month: value })
  }

  private onYearChange(value: number | "all"): void {
    let updatedFilter = { ...this.filter, year: value }

    if (value === VALUE_ALL) {
      updatedFilter.month = VALUE_ALL
    }

    this.$emit("change", updatedFilter)
  }

  readonly VALUE_ALL = VALUE_ALL
}
</script>
