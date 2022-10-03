<template>
  <AppLayout>
    <div v-if="loading">
      <BaseProgressBarLinear />
    </div>
    <div v-else class="application-settings-wrapper">
      <v-img
        max-width="300"
        contain
        :src="
          applicationDetail && applicationDetail.logo
            ? applicationDetail.logo
            : require('@/assets/svg/app-item-placeholder.svg')
        "
        class="my-5"
      />
      <Heading class="mb-2">{{
        applicationDetail ? applicationDetail.publicName : application.appName
      }}</Heading>
      <p>
        {{ applicationDetail && applicationDetail.description }}
      </p>
      <div class="wrapper my-5">
        <StatusCard
          :loading="loading"
          :score="application.endUsers"
          :title="$t('applicationDetailPage.users')"
        />
        <StatusCard
          :loading="loading"
          :score="application.installCount"
          :title="$t('applicationDetailPage.installations')"
        />
        <StatusCard
          :loading="loading"
          :score="formatNumber(application.totalCost)"
          :title="$t('applicationDetailPage.cost')"
        />
      </div>
      <LineChart
        class="chart-js"
        v-if="labels.length > 0"
        :chart-data="data"
        :chart-labels="labels"
      />
    </div>
  </AppLayout>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Watch } from "vue-property-decorator";
import AppLayout from "../components/commons/layouts/AppLayout.vue";
import StatusCard from "@/components/commons/layouts/StatusCard.vue";
import { Routes } from "@/enums";
import LineChart from "@/components/app/LineChart.vue";
import BaseProgressBarLinear from "@/components/commons/BaseProgressBarLinear.vue";
import Heading from "@/components/commons/typography/Heading.vue";
import { Getter } from "vuex-class";
import { AuthGetters, authNamespace, User } from "@/store/modules/auth";
import { callApi } from "@/utils";
import {
  UsageStatsAppsRequest,
  UsageStatsAppsRowsInner,
  UsageStatsTimeBucketUsersRequest,
} from "@/api/generated";
import { api } from "@/api";
import { ApplicationDetail, IndexedApplicationDetail } from "@/types";
import { formatNumber } from "@/filters/number";
import {
  ApplicationsGetters,
  applicationsNamespace,
} from "@/store/modules/applications";

@Component({
  components: {
    Heading,
    BaseProgressBarLinear,
    LineChart,
    StatusCard,
    AppLayout,
  },
})
export default class ApplicationDetailPage extends Vue {
  @Getter(`${authNamespace}/${AuthGetters.GetUser}`)
  currentUser!: User;

  @Getter(
    `${applicationsNamespace}/${ApplicationsGetters.IsFetchingApplicationsMetadata}`
  )
  fetchingMetadata!: boolean;

  @Getter(
    `${applicationsNamespace}/${ApplicationsGetters.GetApplicationsMetadata}`
  )
  applicationsMetadata!: IndexedApplicationDetail;

  routes = Routes;

  loading = false;

  applicationDetail!: ApplicationDetail | null;

  application: UsageStatsAppsRowsInner = {};

  labels: string[] = [];
  data: number[] = [];

  async created() {
    this.loading = true;

    const selectedApplications = await callApi<UsageStatsAppsRequest>(
      api.overview.apps,
      {
        timeRangeStart: new Date(0).toISOString(),
        timeRangeEnd: new Date().toISOString(),
        appName: this.$route.params.id,
      }
    );

    if (selectedApplications.length > 0) {
      this.application = selectedApplications[0];
      this.applicationDetail =
        this.applicationsMetadata[this.application.appId as string];
    }

    const graphData = await callApi<UsageStatsTimeBucketUsersRequest>(
      api.timeBucketUsers.data,
      {
        timeRangeStart: new Date(0).toISOString(),
        timeRangeEnd: new Date().toISOString(),
        appName: this.$route.params.id,
      }
    );

    if (graphData.length > 0) {
      for (const item of graphData) {
        this.labels.push(item.timeBucketName);
        this.data.push(item.endUsers);
      }
    }

    this.loading = false;
  }

  private formatNumber = formatNumber;

  @Watch("fetchingMetadata")
  private updateApplicationMetadata() {
    if (!this.fetchingMetadata) {
      this.loading = true;
      this.applicationDetail =
        this.applicationsMetadata[this.application.appId as string];
      this.loading = false;
    }
  }
}
</script>

<style lang="scss" scoped>
.wrapper {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0 16px;
}
.chart-js {
  max-height: 50px;
}
.application-settings-wrapper {
  max-width: 80ch;
}
</style>
