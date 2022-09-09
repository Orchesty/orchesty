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
      <Heading class="mb-2">Application Name</Heading>
      <p>
        Lambda is a compute service that lets you run code without prosivioning
        or managing servers
      </p>
      <div class="wrapper my-5">
        <StatusCard :score="92" title="Users" />
        <StatusCard loading :score="199" title="Price" />
        <StatusCard :score="18308" title="Billing" />
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
import { callApi } from "@/utils";
import { UsageStatsAppsRequest } from "@/api/generated";
import { api } from "@/api";
import { Getter } from "vuex-class";
import { AuthGetters, authNamespace, User } from "@/store/modules/auth";

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

  application = {};

  labels = ["January", "February", "March", "April", "May", "June"];
  data = [16, 10, 5, 2, 20, 30, 45];

  async created() {
    this.loading = true;
    this.application = await callApi<UsageStatsAppsRequest>(api.overview.apps, {
      timeRangeStart: new Date(0).toISOString(),
      timeRangeEnd: new Date().toISOString(),
      tenantId: this.currentUser.tenantId ?? undefined,
    });
    this.loading = false;
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
