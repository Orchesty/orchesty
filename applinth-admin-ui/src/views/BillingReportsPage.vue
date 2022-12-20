<template>
  <AppLayout>
    <div class="">
      <Heading class="mb-2">{{ $t("billingReportsPage.title") }}</Heading>

      <BillingReportsFilter @change="onChange" :filter="filter" />

      <div class="wrapper my-5">
        <StatusCard
          :loading="loading"
          :score="applicationsCount"
          :title="$t('billingReportsPage.applications')"
        />
        <StatusCard
          :loading="loading"
          :score="installationCount"
          :title="$t('billingReportsPage.installations')"
        />
        <StatusCard
          :loading="loading"
          :score="toCZK(totalCost)"
          :title="$t('billingReportsPage.cost')"
        />
      </div>

      <BillingReportsTable :items="tableItems" />
    </div>
  </AppLayout>
</template>

<script lang="ts">
import Vue from "vue"
import { Component } from "vue-property-decorator"
import AppLayout from "../components/commons/layouts/AppLayout.vue"
import LineChart from "@/components/app/LineChart.vue"
import BaseProgressBarLinear from "@/components/commons/BaseProgressBarLinear.vue"
import Heading from "@/components/commons/typography/Heading.vue"
import { Getter } from "vuex-class"
import { AuthGetters, authNamespace, User } from "@/store/modules/auth"
import { callApi } from "@/utils"
import { UsageStatsAppsRequest, UsageStatsAppsRowsInner } from "@/api/generated"
import { api } from "@/api"
import { toCZK } from "@/filters/money"
import BillingReportsTable from "@/components/app/BillingReportsTable.vue"
import BillingReportsFilter, {
  HistoryFilterType,
  VALUE_ALL,
} from "@/components/app/filters/BillingReportsFilter.vue"
import { DateTime } from "luxon"
import { HistoryTableApplicationItemType } from "@/types"
import StatusCard from "@/components/status-cards/StatusCard.vue"

const PRICE = 19900000

// todo PIP-1365 add graph
@Component({
  components: {
    BillingReportsFilter,
    BillingReportsTable,
    Heading,
    BaseProgressBarLinear,
    LineChart,
    StatusCard,
    AppLayout,
  },
})
export default class BillingReportsPage extends Vue {
  @Getter(`${authNamespace}/${AuthGetters.GetUser}`)
  currentUser!: User

  loading = false
  applicationsCount = PRICE
  installationCount = 10
  totalCost = PRICE
  tableItems!: HistoryTableApplicationItemType[]
  granularity: "monthly" | "daily" = "monthly"
  filter: HistoryFilterType = { year: 2022, month: VALUE_ALL }

  created() {
    this.fetchData()
  }

  private onChange(filter: HistoryFilterType): void {
    if (filter.month !== VALUE_ALL) this.granularity = "daily"
    else this.granularity = "monthly"

    this.filter = filter

    this.fetchData()
  }

  private async fetchData(): Promise<void> {
    this.loading = true
    const filterDateFrom =
      this.filter.year === VALUE_ALL
        ? DateTime.utc(1970, 1).startOf("month")
        : DateTime.utc(
            this.filter.year,
            this.filter.month === VALUE_ALL ? 1 : this.filter.month
          ).startOf("month")

    const filterDateTo =
      this.filter.year === VALUE_ALL
        ? DateTime.utc(DateTime.now().year, 12).endOf("month")
        : DateTime.utc(
            this.filter.year,
            this.filter.month === VALUE_ALL ? 12 : this.filter.month
          ).endOf("month")

    const applications: UsageStatsAppsRowsInner[] =
      await callApi<UsageStatsAppsRequest>(api.overview.apps, {
        granularity: "monthly",
        timeRangeStart: filterDateFrom.toISO(),
        timeRangeEnd: filterDateTo.toISO(),
      })

    this.recalculateValues(applications)
  }

  private recalculateValues(applications: UsageStatsAppsRowsInner[]): void {
    this.applicationsCount = applications.length
    let installationCountAccumulator = 0
    let totalCostAccumulator = 0

    this.tableItems = applications.map((application) => {
      installationCountAccumulator += application.installCount || 0
      totalCostAccumulator += application.totalCost || 0

      return { ...application, pricePerInstance: PRICE } // todo PIP-1365 load from BE
    })

    this.installationCount = installationCountAccumulator
    this.totalCost = totalCostAccumulator

    this.loading = false
  }

  readonly toCZK = toCZK
}
</script>

<style lang="scss" scoped>
.wrapper {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 16px;
}
</style>
