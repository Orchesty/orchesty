<template>
  <AppLayout :detail-page-title="breadcrumbTitle">
    <div v-if="loading">
      <BaseProgressBarLinear />
    </div>
    <div v-else class="application-settings-wrapper">
      <v-img
        max-width="150"
        contain
        :src="
          applicationDetail && applicationDetail.logo
            ? applicationDetail.logo
            : require('@/assets/svg/app-item-placeholder.svg')
        "
        class="my-5"
        :alt="breadcrumbTitle"
      />
      <Heading class="mb-2">{{ breadcrumbTitle }}</Heading>
      <p>
        {{ applicationDetail && applicationDetail.description }}
      </p>
      <div class="wrapper my-5">
        <StatusCard
          :loading="loading"
          :score="toCZK(monthlyPrice)"
          :title="$t('applicationDetailPage.monthlyPrice')"
        />
        <StatusCard
          :loading="loading"
          :score="application.endUsers"
          :title="$t('applicationDetailPage.customers')"
        />
        <StatusCard
          :loading="loading"
          :score="toCZK(application.totalCost)"
          :title="$t('applicationDetailPage.cost')"
        />
        <StatusCard
          :loading="loading"
          :score="toCZK(application.estimatedTotalCost)"
          :title="$t('overviewPage.statusCards.estimatedCostsEom')"
        />
      </div>
      <!--      <LineChart-->
      <!--        class="chart-js"-->
      <!--        v-if="labels.length > 0"-->
      <!--        :chart-data="data"-->
      <!--        :chart-labels="labels"-->
      <!--      />-->
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
import { toCZK } from "@/filters/money";
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
  breadcrumbTitle: string | undefined = "";
  monthlyPrice = 19900000;

  async created() {
    this.loading = true;

    const selectedApplications = await callApi<UsageStatsAppsRequest>(
      api.overview.apps,
      {
        appId: this.$route.params.id,
        granularity: "monthly",
        tail: true,
      }
    );

    this.applicationDetail = this.applicationsMetadata[this.$route.params.id];

    if (selectedApplications.length > 0) {
      this.application = selectedApplications[0];

      this.breadcrumbTitle =
        this.applicationDetail?.publicName || this.application.appName;
    } else {
      this.breadcrumbTitle = this.$route.params.id;
    }

    const graphData = await callApi<UsageStatsTimeBucketUsersRequest>(
      api.timeBucketUsers.data,
      {
        timeRangeStart: new Date(0).toISOString(),
        timeRangeEnd: new Date().toISOString(),
        appId: this.$route.params.id,
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

  readonly toCZK = toCZK;

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
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 16px;
}
.chart-js {
  max-height: 50px;
}
.application-settings-wrapper {
  max-width: 80ch;
}
</style>
