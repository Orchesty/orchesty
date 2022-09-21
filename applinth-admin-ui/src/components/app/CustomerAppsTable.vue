<template>
  <SimpleTable
    :loading="isLoading"
    class="table-medium"
    :headers="headers"
    :items="installedApps"
  />
</template>

<script lang="ts">
import { Component, Prop, Vue } from "vue-property-decorator";
import SelectBox from "@/components/commons/inputsAndControls/SelectBox.vue";
import TextField from "@/components/commons/inputsAndControls/TextField.vue";
import Table from "@/components/commons/tables/Table.vue";
import {
  UsageStatsInstalledAppsRequest,
  UsageStatsInstalledAppsRowsInner,
} from "@/api/generated";
import { callApi } from "@/utils";
import { api } from "@/api";
import SimpleTable from "@/components/commons/tables/SimpleTable.vue";

@Component({
  components: {
    SimpleTable,
    Table,
    TextField,
    SelectBox,
  },
})
export default class CustomerAppsTable extends Vue {
  @Prop({ type: String, required: true })
  customerId!: string;

  isLoading = false;
  installedApps: UsageStatsInstalledAppsRowsInner[] = [];

  headers = [
    {
      text: "grids.headers.activeApps",
      sortable: true,
      align: "start",
      value: "appName",
    },
    {
      text: "grids.headers.installed",
      sortable: true,
      align: "start",
      value: "installed",
    },
  ];

  async created() {
    this.isLoading = true;
    this.installedApps = await callApi<UsageStatsInstalledAppsRequest>(
      api.installedApps.apps,
      {
        endUserId: this.customerId,
      }
    );
    this.isLoading = false;
  }
}
</script>
