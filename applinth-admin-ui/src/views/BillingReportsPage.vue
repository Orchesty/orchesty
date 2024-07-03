<template>
  <AppLayout>
    <div class="">
      <Heading class="mb-2">{{ $t("billingReportsPage.title") }}</Heading>

      <BillingReportsFilter
        @change="onChangeFilterValue"
        :filter="filter"
        :options="filterOptions"
      />

      <div class="wrapper my-2">
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

      <div class="mb-5">
        <StatusCardCostInfo />
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
import {
  UsageStatsApps,
  UsageStatsAppsRequest,
  UsageStatsAppsRowsInner,
  UsageStatsTimeBucketHistoryRequest,
} from "@/api/generated"
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
import StatusCardCostInfo from "@/components/status-cards/StatusCardCostInfo.vue"
import {
  getTimeRangeEndForApiCall,
  getTimeRangeStartForApiCall,
} from "@/service/billingService"

export interface IFilterYearMonthOptions {
  [key: number]: number[]
}

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
    StatusCardCostInfo,
  },
})
export default class BillingReportsPage extends Vue {
  @Getter(`${authNamespace}/${AuthGetters.GetUser}`)
  currentUser!: User

  loading = false
  applicationsCount = 0
  installationCount = 10
  totalCost = 0
  tableItems: HistoryTableApplicationItemType[] = []
  granularity: "monthly" | "daily" = "monthly"
  filter: HistoryFilterType = { year: DateTime.now().year, month: VALUE_ALL }
  filterOptions: IFilterYearMonthOptions = {}

  created() {
    this.fetchData()
    this.fetchFilterDateRange()
  }

  private onChangeFilterValue(filter: HistoryFilterType): void {
    if (filter.month !== VALUE_ALL) this.granularity = "daily"
    else this.granularity = "monthly"

    this.filter = filter

    this.fetchData()
  }

  private async fetchFilterDateRange(): Promise<void> {
    const { billingHistoryStart, billingHistoryEnd } =
      await callApi<UsageStatsTimeBucketHistoryRequest>(
        api.timeBucketHistory.data,
        {
          granularity: "monthly",
          timeRangeStart: DateTime.utc(1970, 1).startOf("month").toISO(),
          timeRangeEnd: DateTime.utc(DateTime.now().year, 12)
            .endOf("month")
            .plus({ second: 1 })
            .toISO(),
        }
      )

    if (billingHistoryEnd && billingHistoryStart) {
      const d1 = DateTime.fromISO(billingHistoryStart)
      const d2 = DateTime.fromISO(billingHistoryEnd)

      this.prepareFilterValues(d1, d2)
    }
  }

  private async fetchData(): Promise<void> {
    this.loading = true
    const filterDateFrom =
      this.filter.year === VALUE_ALL
        ? getTimeRangeStartForApiCall()
        : getTimeRangeStartForApiCall(
            this.filter.year,
            this.filter.month === VALUE_ALL ? 1 : this.filter.month
          )

    const filterDateTo =
      this.filter.year === VALUE_ALL
        ? getTimeRangeEndForApiCall()
        : getTimeRangeEndForApiCall(
            this.filter.year,
            this.filter.month === VALUE_ALL ? 12 : this.filter.month
          )

    const applications: UsageStatsApps = await callApi<UsageStatsAppsRequest>(
      api.overviewFull.full,
      {
        granularity: "monthly",
        timeRangeStart: filterDateFrom.toISO(),
        timeRangeEnd: filterDateTo.plus({ second: 1 }).toISO(),
      }
    )

    this.recalculateValues(
      applications.rows ?? [],
      applications.modulePrices ?? {}
    )
  }

  private recalculateValues(
    applications: UsageStatsAppsRowsInner[],
    prices: Record<string, number>
  ): void {
    this.applicationsCount = applications.length
    let installationCountAccumulator = 0
    let totalCostAccumulator = 0

    this.tableItems = applications.map((application) => {
      installationCountAccumulator += application.installCount || 0
      totalCostAccumulator += application.totalCost || 0

      return {
        ...application,
        pricePerInstance: prices[application.appName ?? ""] ?? 0,
      }
    })

    this.installationCount = installationCountAccumulator
    this.totalCost = totalCostAccumulator

    this.loading = false
  }

  private prepareFilterValues(d1: DateTime, d2: DateTime): void {
    const filterOptions: Record<number, number[]> = {}

    const beginYear: number = d1.year
    const endYear: number = d2.year

    for (let year = beginYear; year <= endYear; year++) {
      filterOptions[year] = this.getMonthsForYearsRange(year, d1, d2)
    }

    this.filterOptions = filterOptions
  }

  private getMonthsForYearsRange(
    selectedYear: number,
    d1: DateTime,
    d2: DateTime
  ): number[] {
    const [beginYear, endYear] = [d1.year, d2.year]
    const [beginMonth, endMonth] = [d1.month, d2.month]

    if (selectedYear === beginYear) {
      if (beginYear === endYear) {
        const months: number[] = []
        for (let month = beginMonth; month <= endMonth; month++) {
          months.push(month)
        }

        return months
      }

      return [...Array(12 - beginMonth + 1).keys()].map((i) => i + beginMonth)
    } else if (selectedYear === endYear) {
      return [...Array(endMonth).keys()].map((i) => i + 1)
    } else {
      return [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12]
    }
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
