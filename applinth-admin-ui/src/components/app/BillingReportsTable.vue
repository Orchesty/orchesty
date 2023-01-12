<template>
  <SimpleTable
    :loading="isLoading"
    class="table-medium"
    :headers="headers"
    :items="items"
    hide-footer
    :items-per-page="PAGINATION_NO_LIMIT"
  >
    <template #pricePerInstance="{ item }">
      <slot>{{ toCZK(item.pricePerInstance) }}</slot>
    </template>
    <template #totalCost="{ item }">
      <slot>{{ toCZK(item.totalCost) }}</slot>
    </template>
  </SimpleTable>
</template>

<script lang="ts">
import { Component, Prop, Vue } from "vue-property-decorator"
import SelectBox from "@/components/commons/inputsAndControls/SelectBox.vue"
import TextField from "@/components/commons/inputsAndControls/TextField.vue"
import SimpleTable from "@/components/commons/tables/SimpleTable.vue"
import { toCZK } from "@/filters/money"
import { HistoryTableApplicationItemType } from "@/types"
import { PAGINATION_NO_LIMIT } from "@/enums"

@Component({
  components: {
    SimpleTable,
    TextField,
    SelectBox,
  },
})
export default class BillingReportsTable extends Vue {
  @Prop({ type: Array, required: true })
  items!: HistoryTableApplicationItemType[]

  isLoading = false

  headers = [
    {
      text: this.$t("grids.headers.application"),
      sortable: true,
      align: "start",
      value: "appName",
    },
    {
      text: this.$t("grids.headers.installations"),
      sortable: true,
      align: "start",
      value: "installCount",
    },
    {
      text: this.$t("grids.headers.pricePerInstallation"),
      sortable: true,
      align: "start",
      value: "pricePerInstance",
    },
    {
      text: this.$t("grids.headers.cost"),
      sortable: true,
      align: "start",
      value: "totalCost",
    },
  ]

  PAGINATION_NO_LIMIT = PAGINATION_NO_LIMIT

  readonly toCZK = toCZK
}
</script>
