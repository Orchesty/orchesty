<template>
  <div>
    <v-row v-if="state.isSending">
      <v-col
        v-for="i in 6"
        :key="i + Math.random()"
        cols="12"
        sm="6"
        md="4"
        xl="2"
      >
        <v-skeleton-loader class="mx-auto" height="102" type="image" />
      </v-col>
    </v-row>
    <v-row v-else>
      <v-col
        v-for="(i, item) of process"
        :key="i + item"
        cols="12"
        sm="6"
        md="4"
        xl="2"
      >
        <v-card class="py-2">
          <v-card-title class="pb-0">
            <div class="text-center body-2 mx-auto">{{ stats[item] }}</div>
          </v-card-title>
          <v-card-title class="pt-0">
            <div class="title mx-auto font-weight-bold">
              {{ process[item] }}
            </div>
          </v-card-title>
        </v-card>
      </v-col>
    </v-row>
    <v-row>
      <v-col cols="12">
        <h3 class="title font-weight-bold">
          {{ $t("page.heading.processes") }}
        </h3>
      </v-col>
    </v-row>
    <v-row dense>
      <topology-processes
        :headers="headers"
        :state="state"
        :items="processes || []"
      />
    </v-row>
    <!--    <v-row v-if="false">-->
    <!--      <v-col cols="12">-->
    <!--        <h4>{{ $t('topologies.dashboard.metrics') }}</h4>-->
    <!--      </v-col>-->
    <!--    </v-row>-->
    <!--    <v-row v-if="false">-->
    <!--      <v-col v-for="(value, name, index) of systemMetrics" :key="index" cols="12" sm="4" md="3">-->
    <!--        <v-card>-->
    <!--          <v-card-title class="d-flex">-->
    <!--            <span class="mx-auto">{{ name }}</span>-->
    <!--          </v-card-title>-->
    <!--          <div class="d-flex position-relative pb-4">-->
    <!--            <doughnut-->
    <!--              :width="150"-->
    <!--              :height="100"-->
    <!--              class="mx-auto position-absolute"-->
    <!--              :chart-data="datasets({ value, name }, true)"-->
    <!--              :options="options(true)"-->
    <!--            />-->
    <!--            <div class="position-absolute-text">-->
    <!--              <span>{{ `${Math.round(value)}%` }}</span>-->
    <!--            </div>-->
    <!--            <doughnut-->
    <!--              :width="150"-->
    <!--              :height="100"-->
    <!--              class="mx-auto"-->
    <!--              :chart-data="datasets({ value, name }, false)"-->
    <!--              :options="options(false)"-->
    <!--            />-->
    <!--          </div>-->
    <!--        </v-card>-->
    <!--      </v-col>-->
    <!--    </v-row>-->
  </div>
</template>

<script>
import { ROUTES } from "@/services/enums/routerEnums"
import { mapActions, mapGetters } from "vuex"
import { TOPOLOGIES } from "@/store/modules/topologies/types"
import { REQUESTS_STATE } from "@/store/modules/api/types"
import { API } from "@/api"
import TopologyProcesses from "@/components/app/dashboard/grid/TopologyProcesses"

export default {
  name: "TopologyDashboard",
  components: { TopologyProcesses },
  methods: {
    ...mapActions(TOPOLOGIES.NAMESPACE, [
      TOPOLOGIES.ACTIONS.DATA.GET_DASHBOARD,
      TOPOLOGIES.ACTIONS.DATA.GET_DASHBOARD_PROCESSES,
    ]),
    color(value) {
      return value >= 60 ? (value >= 80 ? "#cc0000" : "#f7b500") : "#0e7d00"
    },
    options(isBackground) {
      return {
        rotation: -Math.PI * 1.15,
        circumference: Math.PI * 1.3,
        responsive: true,
        maintainAspectRatio: false,
        cutoutPercentage: isBackground ? 90 : 60,
        legend: {
          display: false,
        },
        title: {
          display: false,
        },
        animation: {
          animateScale: false,
          animateRotate: true,
        },
      }
    },
    backgroundData() {
      return {
        data: [60, 20, 20],
        backgroundColor: ["#0e7d00", "#f7b500", "#cc0000"],
      }
    },
    foregroundData(metric) {
      return {
        data: [metric.value, 100 - metric.value],
        backgroundColor: [this.color(metric.value), "transparent"],
        label: metric.name,
      }
    },
    datasets(metric, isBackground) {
      return {
        datasets: [
          isBackground
            ? { ...this.backgroundData() }
            : { ...this.foregroundData(metric) },
        ],
        labels: [metric.name, "remaining"],
      }
    },
  },
  data() {
    return {
      ROUTES,
      headers: [
        { text: this.$t("grid.header.topologyName"), value: "topologyId" },
        { text: this.$t("grid.header.created"), value: "started" },
        {
          text: this.$t("grid.header.duration"),
          value: "duration",
          align: "left",
        },
        {
          text: this.$t("grid.header.progress"),
          value: "progress",
          align: "left",
        },
        { text: this.$t("grid.header.status"), value: "status", align: "left" },
        {
          text: this.$t("grid.header.correlation_id"),
          value: "correlation_id",
          align: "right",
          width: "150px",
        },
      ],
      stats: {
        activeTopologies: this.$t("page.status.activeTopologies"),
        disabledTopologies: this.$t("page.status.disabledTopologies"),
        totalRuns: this.$t("page.status.totalRuns"),
        errorsCount: this.$t("page.status.errorsCount"),
        successCount: this.$t("page.status.successCount"),
        installedApps: this.$t("page.status.installedApps"),
      },
    }
  },
  computed: {
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    ...mapGetters(TOPOLOGIES.NAMESPACE, {
      topologiesOverview: TOPOLOGIES.GETTERS.GET_TOPOLOGIES_OVERVIEW,
      processes: TOPOLOGIES.GETTERS.GET_DASHBOARD_PROCESSES,
    }),
    state() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE]([
        API.topology.getDashboard.id,
      ])
    },
    process() {
      return this.topologiesOverview?.process
    },
    systemMetrics() {
      return this.topologiesOverview?.systemMetrics
    },
    filter() {
      return this.topologiesOverview?.filter
    },
  },
  async created() {
    await this[TOPOLOGIES.ACTIONS.DATA.GET_DASHBOARD]()
    await this[TOPOLOGIES.ACTIONS.DATA.GET_DASHBOARD_PROCESSES]()
  },
}
</script>

<style lang="scss" scoped>
.position-relative {
  position: relative;
}
.position-absolute {
  margin-left: auto;
  margin-right: auto;
  left: 0;
  right: 0;
  text-align: center;
  position: absolute;
}
.position-absolute-text {
  @extend .position-absolute;
  top: 55px;
}
.dashboardCol {
  flex: 0 1 25%;
  @media #{map-get($display-breakpoints, 'sm-and-down')} {
    flex: 0 1 100%;
  }
}
</style>
