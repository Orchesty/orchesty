<template>
  <div class="d-flex">
    <div class="mr-2">
      <SelectBox
        :label="$t('formLabels.year')"
        :items="optionsYears"
        name="year"
        @change="onYearChange"
        :value="filter.year"
      />
    </div>
    <SelectBox
      :label="$t('formLabels.month')"
      :items="optionsMonths"
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
import { IFilterYearMonthOptions } from "@/views/BillingReportsPage.vue"

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

  @Prop({ type: Object, required: true })
  options!: IFilterYearMonthOptions

  OPTION_ALL = { text: this.$t("all"), value: VALUE_ALL }

  get optionsMonths() {
    if (this.filter.year === this.VALUE_ALL) {
      return [this.OPTION_ALL]
    }
    return [
      this.OPTION_ALL,
      ...(this.options[this.filter.year]?.map((month) => ({
        text: `${month}`,
        value: month,
      })) || []),
    ]
  }

  get optionsYears() {
    return [
      this.OPTION_ALL,
      ...(Object.keys(this.options)?.map((year) => ({
        text: `${year}`,
        value: Number(year),
      })) || []),
    ]
  }

  private onMonthChange(value: number | "all") {
    if (value && this.filter.year === VALUE_ALL) {
      return
    }

    this.$emit("change", { ...this.filter, month: value })
  }

  private onYearChange(value: number | "all"): void {
    let updatedFilter = { ...this.filter, year: value, month: VALUE_ALL }

    this.$emit("change", updatedFilter)
  }

  readonly VALUE_ALL = VALUE_ALL
}
</script>
