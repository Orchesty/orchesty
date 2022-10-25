<template>
  <SimpleTable
    :loading="isLoading"
    class="table-medium"
    :headers="headers"
    :items="installedApps"
    hide-footer
  >
    <template #installed="{ item }">
      <span>{{ toLocalDate(item.installed) }}</span>
    </template>
  </SimpleTable>
</template>

<script lang="ts">
import { Component, Prop, Vue } from "vue-property-decorator"
import SelectBox from "@/components/commons/inputsAndControls/SelectBox.vue"
import TextField from "@/components/commons/inputsAndControls/TextField.vue"
import {
  UsageStatsInstalledAppsRequest,
  UsageStatsInstalledAppsRowsInner,
} from "@/api/generated"
import { callApi } from "@/utils"
import { api } from "@/api"
import SimpleTable from "@/components/commons/tables/SimpleTable.vue"
import { toLocalDate } from "@/filters/datetime"

@Component({
  components: {
    SimpleTable,
    TextField,
    SelectBox,
  },
  filters: {
    toLocalDate,
  },
})
export default class CustomerAppsTable extends Vue {
  @Prop({ type: String, required: true })
  customerId!: string

  isLoading = false
  installedApps!: UsageStatsInstalledAppsRowsInner[]

  headers = [
    {
      text: this.$t("grids.headers.activeApplications"),
      sortable: true,
      align: "start",
      value: "appName",
    },
    {
      text: this.$t("grids.headers.installed"),
      sortable: true,
      align: "start",
      value: "installed",
    },
  ]

  async created() {
    this.isLoading = true
    this.installedApps = await callApi<UsageStatsInstalledAppsRequest>(
      api.installedApps.apps,
      {
        endUserId: this.customerId,
        tail: true,
      }
    )

    this.isLoading = false
  }

  private toLocalDate = toLocalDate
}
</script>
