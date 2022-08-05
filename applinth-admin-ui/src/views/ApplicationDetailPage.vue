<template>
  <AppLayout>
    <div class="w-600">
      <router-link :to="{ name: routes.Dashboard }" class="link">
        <span>Back to applications</span>
      </router-link>
      <v-img
        max-width="400"
        contain
        src="https://picsum.photos/id/11/500/300"
        class="my-5"
      />
      <h1>Amazon Lambda</h1>
      <p>
        Lambda is a compute service that lets you run code without prosivioning
        or managing servers
      </p>
      <div class="wrapper my-5">
        <StatusCard :score="92" title="Users" />
        <StatusCard :score="199" title="Price" />
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
import StatusCard from "@/components/commons/StatusCard.vue";
import { Routes } from "@/enums";
import LineChart from "@/components/app/LineChart.vue";

@Component({
  components: {
    LineChart,
    StatusCard,
    AppLayout,
  },
})
export default class ApplicationDetailPage extends Vue {
  routes = Routes;

  labels = ["January", "February", "March", "April", "May", "June"];
  data = [16, 10, 5, 2, 20, 30, 45];
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
