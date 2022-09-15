<template>
  <AppLayout>
    <div v-if="loading">
      <BaseProgressBarLinear />
    </div>
    <div v-else class="w-600">
      <!--      TODO HARDCOCED-->
      <v-img
        max-width="400"
        contain
        src="https://picsum.photos/id/11/500/300"
        class="my-5"
      />
      <Heading class="mb-2">{{ application.appName }}</Heading>
      <p>
        Lambda is a compute service that lets you run code without prosivioning
        or managing servers todo...
      </p>
      <div class="wrapper my-5">
        <StatusCard
          :loading="loading"
          :score="application.endUsers"
          title="Users"
        />
        <StatusCard :loading="loading" :score="199" title="Price" />
        <StatusCard
          :loading="loading"
          :score="application.totalCost"
          title="Billing"
        />
      </div>
      <LineChart class="chart-js" :chart-data="data" :chart-labels="labels" />
    </div>
  </AppLayout>
</template>

<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
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
  UsageStatsTimeBucketUsersRowsInner,
} from "@/api/generated";
import { api } from "@/api";

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

  routes = Routes;

  loading = false;

  application: UsageStatsAppsRowsInner = {};
  graphData: UsageStatsTimeBucketUsersRowsInner = {};

  labels = ["January", "February", "March", "April", "May", "June"];
  data = [16, 10, 5, 2, 20, 30, 45];

  async created() {
    this.loading = true;

    const selectedApplications = await callApi<UsageStatsAppsRequest>(
      api.overview.apps,
      {
        timeRangeStart: new Date(0).toISOString(),
        timeRangeEnd: new Date().toISOString(),
        tenantId: this.currentUser.tenantId ?? undefined,
        appName: this.$route.params.id,
      }
    );

    if (selectedApplications.length > 0)
      this.application = selectedApplications[0];

    const data = await callApi<UsageStatsTimeBucketUsersRequest>(
      api.timeBucketUsers.data,
      {
        timeRangeStart: new Date(0).toISOString(),
        timeRangeEnd: new Date().toISOString(),
        tenantId: this.currentUser.tenantId ?? undefined,
        appName: this.$route.params.id,
      }
    );

    if (data.length > 0) this.graphData = data[0];

    console.log("app", selectedApplications);
    console.log("data", data);
    this.loading = false;

    //todo change page title
    // document.title = `Applinth | ${this.application.displayName}`;
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
  max-height: 200px;
}
.w-600 {
  width: 600px;
}
</style>
