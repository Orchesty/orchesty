<template>
  <SimpleTable
    :loading="isLoading"
    class="table-medium"
    :headers="headers"
    :items="monthlyBills"
  />
</template>

<script lang="ts">
import { Component, Prop, Vue } from "vue-property-decorator";
import Table from "@/components/commons/tables/Table.vue";
import {
  UsageStatsTimeBucketAppsRequest,
  UsageStatsTimeBucketAppsRowsInner,
} from "@/api/generated";
import { callApi } from "@/utils";
import { api } from "@/api";
import SimpleTable from "@/components/commons/tables/SimpleTable.vue";

@Component({
  components: {
    SimpleTable,
    Table,
  },
})
export default class CustomerBillingTable extends Vue {
  @Prop({ type: String, required: true })
  customerId!: string;

  isLoading = false;
  monthlyBills: UsageStatsTimeBucketAppsRowsInner[] = [];

  headers = [
    {
      text: "grids.headers.month",
      sortable: true,
      align: "start",
      value: "month",
    },
    {
      text: "grids.headers.application",
      sortable: true,
      align: "start",
      value: "appName",
    },
    {
      text: "grids.headers.billing",
      sortable: true,
      align: "start",
      value: "totalCost",
    },
  ];

  async created() {
    this.isLoading = true;
    this.monthlyBills = await callApi<UsageStatsTimeBucketAppsRequest>(
      api.overview.apps,
      {
        timeRangeStart: new Date(0).toISOString(),
        timeRangeEnd: new Date().toISOString(),
        endUserId: this.customerId,
      }
    );
    this.isLoading = false;
  }
}
</script>
