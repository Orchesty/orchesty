<template>
  <div>
    <v-row>
      <v-col>
        <heading class="mb-4">
          {{ $t("overviewPage.installedApps.heading") }}
        </heading>
        <app-installed-items />
      </v-col>
    </v-row>
    <v-row>
      <v-col class="text-right">
        <router-link
          :to="{
            name: ROUTES.APPLICATIONS,
          }"
        >
          {{ $t("button.addApplication") }}
        </router-link>
      </v-col>
    </v-row>
    <v-row>
      <v-col>
        <heading class="mb-4">
          {{ $t("overviewPage.overview.heading") }}
        </heading>
        <data-grid
          ref="gridOverview"
          fetch-on-init
          :headers="headers"
          :grid-settings="GRIDS.OVERVIEW"
          :sort-by="['started']"
          :sort-desc="[true]"
          item-key="id"
        >
          <template #default="{ items }">
            <td>
              {{ getTopologyName(items.item) }}
            </td>
            <td>
              {{ toLocalDateTime(items.item.started) }}
            </td>
            <td>
              {{ getProcessFinishTime(items.item) }}
            </td>
            <td>
              {{ getProcessDurationTime(items.item) }}
            </td>
            <td>
              {{ items.item.nodesProcessed + "/" + items.item.nodesTotal }}
            </td>
            <td>
              {{ items.item.status }}
            </td>
          </template>
        </data-grid>
      </v-col>
    </v-row>
  </div>
</template>

<script>
import DataGrid from "@/components/commons/DataGrid"
import { GRIDS } from "@/utils/gridsConfig"
import {
  toLocalDateTime,
  toLocalTime,
} from "@/localization/filters/dateFilters"
import AppInstalledItems from "@/components/commons/AppInstalledItems"
import { ROUTES } from "@/router/routes"
import Heading from "@/components/commons/Heading"
import moment from "moment"
import prettifyMills from "pretty-ms"
import { callApi } from "@/utils/apiFetch"
import { API } from "@/api"

export default {
  name: "OverviewPage",
  components: {
    Heading,
    AppInstalledItems,
    DataGrid,
  },
  async beforeRouteEnter(_to, _from, next) {
    const installedApps = await callApi({
      requestData: API.appStore.getInstalledApps,
    })
    if (installedApps.items.length <= 0) {
      next((vm) => {
        // In case there are no installed apps yet,
        // the user will be redirected to choose
        // an app to install.
        vm.$router.push({ name: ROUTES.APPLICATIONS })
      })
    } else {
      next()
    }
  },
  data() {
    return {
      headers: [
        {
          text: this.$t("grid.overview.header.process"),
          value: "process",
          align: "start",
          sortable: true,
        },
        {
          text: this.$t("grid.overview.header.started"),
          value: "started",
          align: "start",
          sortable: true,
        },
        {
          text: this.$t("grid.overview.header.finished"),
          value: "finished",
          align: "start",
          sortable: true,
        },
        {
          text: this.$t("grid.overview.header.duration"),
          value: "duration",
          align: "start",
          sortable: true,
        },
        {
          text: this.$t("grid.overview.header.progress"),
          value: "progress",
          align: "start",
          sortable: true,
        },
        {
          text: this.$t("grid.overview.header.status"),
          value: "status",
          align: "start",
          sortable: true,
        },
      ],
      GRIDS,
      toLocalDateTime,
      toLocalTime,
      prettifyMills,
      ROUTES,
    }
  },
  methods: {
    getTopologyName(item) {
      return item.process ?? item.topologyName
    },
    getProcessFinishTime(process) {
      return this.isInProgress(process.status)
        ? "-"
        : toLocalDateTime(process.finished)
    },
    isInProgress(value) {
      return value.toLowerCase() === "in progress"
    },
    getProcessDurationTime(process) {
      if (this.isInProgress(process.status)) {
        const processStartedMilliseconds = moment(process.started).format("x")
        const currentTimeMilliseconds = moment().format("x")

        return this.prettifyMillsWithDecimals(
          currentTimeMilliseconds - processStartedMilliseconds
        )
      } else {
        return this.prettifyMillsWithDecimals(process.duration)
      }
    },

    prettifyMillsWithDecimals(milliseconds) {
      return this.prettifyMills(milliseconds, {
        keepDecimalsOnWholeSeconds: true,
      })
    },
  },
}
</script>
