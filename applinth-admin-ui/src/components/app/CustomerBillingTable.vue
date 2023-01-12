<template>
  <SimpleTable
    :loading="isLoading"
    :headers="headers"
    :items="monthlyBills"
    :items-per-page="PAGINATION_NO_LIMIT"
    hide-footer
  >
    <template #appNames="{ item }">
      {{ stringifyArray(item.appNames) }}
    </template>
    <template #totalCost="{ item }">
      <slot>{{ toCZK(item.totalCost) }}</slot>
      <span class="text-lowercase">
        {{ suffixToCurrentMonth(item.timeBucketName) }}
      </span>
    </template>
  </SimpleTable>
</template>

<script lang="ts">
import { Component, Prop, Vue } from "vue-property-decorator"
import { DateTime } from "luxon"
import {
  UsageStatsTimeBucketAppsRequest,
  UsageStatsTimeBucketAppsRowsInner,
} from "@/api/generated"
import { callApi } from "@/utils"
import { api } from "@/api"
import SimpleTable from "@/components/commons/tables/SimpleTable.vue"
import { toCZK } from "@/filters/money"
import {
  getTimeRangeEndForApiCall,
  getTimeRangeStartForApiCall,
} from "@/service/billingService"
import { PAGINATION_NO_LIMIT } from "@/enums"

@Component({
  components: {
    SimpleTable,
  },
})
export default class CustomerBillingTable extends Vue {
  @Prop({ type: String, required: true })
  customerId!: string

  isLoading = false
  monthlyBills: UsageStatsTimeBucketAppsRowsInner[] = []

  headers = [
    {
      text: this.$t("grids.headers.month"),
      sortable: true,
      align: "start",
      value: "timeBucketName",
    },
    {
      text: this.$t("grids.headers.application"),
      sortable: true,
      align: "start",
      value: "appNames",
    },
    {
      text: this.$t("grids.headers.cost"),
      sortable: true,
      align: "start",
      value: "totalCost",
    },
  ]

  PAGINATION_NO_LIMIT = PAGINATION_NO_LIMIT

  async created() {
    this.isLoading = true
    this.monthlyBills = await callApi<UsageStatsTimeBucketAppsRequest>(
      api.timeBucketApps.apps,
      {
        timeRangeStart: getTimeRangeStartForApiCall().toISO(),
        timeRangeEnd: getTimeRangeEndForApiCall().toISO(),
        endUserId: this.customerId,
      }
    )

    this.isLoading = false
  }

  private suffixToCurrentMonth(monthString: string): string {
    return DateTime.utc().hasSame(
      DateTime.fromFormat(monthString, "MM/yy"),
      "month"
    )
      ? `(${this.$t("customerDetailPage.mayChange")})`
      : ""
  }

  stringifyArray(array: Array<string> | undefined): string {
    if (Array.isArray(array)) return array.join(", ")
    return ""
  }

  readonly toCZK = toCZK
}
</script>
