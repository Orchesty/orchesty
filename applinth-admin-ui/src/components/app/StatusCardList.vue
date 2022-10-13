<template>
  <div class="wrapper">
    <StatusCard
      :loading="isLoading"
      :score="applicationsCount"
      :title="$t('overviewPage.statusCards.applications')"
    />
    <StatusCard
      :loading="isLoading"
      :score="installationsCount"
      :title="$t('overviewPage.statusCards.installations')"
    />
    <StatusCard
      :loading="isLoading"
      :score="customersCount"
      :title="$t('overviewPage.statusCards.activeCustomers')"
    />
    <StatusCard
      :loading="isLoading"
      :score="toCZK(amount)"
      :title="$t('overviewPage.statusCards.currentCost')"
    />
    <StatusCard
      :loading="isLoading"
      :score="toCZK(estimatedCosts)"
      :title="$t('overviewPage.statusCards.estimatedCostsEom')"
    />
  </div>
</template>

<script lang="ts">
import { Component, Vue } from "vue-property-decorator";
import StatusCard from "../commons/layouts/StatusCard.vue";
import { callApi } from "@/utils";
import { UsageStatsAppsRequest, UsageStatsUsersRequest } from "@/api/generated";
import { api } from "@/api";
import { toCZK } from "@/filters/money";

@Component({
  components: {
    StatusCard,
  },
})
export default class StatusCardList extends Vue {
  applicationsCount = 0;
  installationsCount = 0;
  customersCount = 0;
  amount = 0;
  estimatedCosts = 0;
  isLoading = false;

  async created() {
    this.isLoading = true;

    const [apps, customers] = await Promise.all([
      callApi<UsageStatsAppsRequest>(api.overview.apps, {
        granularity: "monthly",
        tail: true,
      }),
      callApi<UsageStatsUsersRequest>(api.customers.list, {
        granularity: "monthly",
        tail: true,
      }),
    ]);

    this.applicationsCount = apps.length;
    this.customersCount = customers.length;

    let installationsCountAccumulator = 0;
    let amountAccumulator = 0;
    let estimatedCostAccumulator = 0;
    for (const app of apps) {
      installationsCountAccumulator += app.endUsers ?? 0;
      amountAccumulator += app.totalCost ?? 0;
      estimatedCostAccumulator += app.estimatedTotalCost ?? 0;
    }

    this.installationsCount = installationsCountAccumulator;
    this.amount = amountAccumulator;
    this.estimatedCosts = estimatedCostAccumulator;

    this.isLoading = false;
  }

  readonly toCZK = toCZK;
}
</script>

<style lang="scss" scoped>
.wrapper {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 0 16px;

  @media (max-width: 700px) {
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
  }
}
</style>
