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
      :score="usersCount"
      :title="$t('overviewPage.statusCards.users')"
    />
    <StatusCard
      :loading="isLoading"
      :score="toCZK(amount)"
      :title="$t('overviewPage.statusCards.amount')"
    />
    <StatusCard
      :loading="isLoading"
      :score="estimatedCost"
      :title="$t('overviewPage.statusCards.estimatedCosts')"
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
  usersCount = 0;
  amount = 0;
  estimatedCost = 0; // todo PIP-1344 počkat, až bude připravený endpoint
  isLoading = false;

  async created() {
    this.isLoading = true;

    const [apps, users] = await Promise.all([
      callApi<UsageStatsAppsRequest>(api.overview.apps),
      callApi<UsageStatsUsersRequest>(api.users.list),
    ]);

    this.applicationsCount = apps.length;
    this.usersCount = users.length;

    let installationsCountAccumulator = 0;
    let amountAccumulator = 0;
    for (const app of apps) {
      installationsCountAccumulator += app.endUsers ?? 0;
      amountAccumulator += app.totalCost ?? 0;
    }

    this.installationsCount = installationsCountAccumulator;
    this.amount = amountAccumulator;

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
