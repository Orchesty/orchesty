<template>
  <div v-if="topologyActive._id">
    <v-row dense>
      <v-col cols="12">
        <quick-grid-filter
          ref="quickGridFilter"
          :quick-filters="quickFilters"
          :filter="filter"
          :filter-meta="filterMeta"
          is-viewer
          :on-change="onFilterChange"
          :is-loading="state.isSending"
        >
          <template #resetClearButtons>
            <v-btn color="primary" icon @click="reload">
              <v-icon> mdi-reload </v-icon>
            </v-btn>
          </template>
        </quick-grid-filter>
      </v-col>
    </v-row>
    <v-row v-if="nodes[0]">
      <v-col cols="12" lg="12">
        <h4 class="primary--text">{{ STATISTICS_ENUM.totalsErrors }}</h4>
        <bar-chart
          :chart-data="chartDataProcess('process')"
          :options="options"
          class="mx-2"
        />
      </v-col>
      <template v-for="key in nodeParameters">
        <v-col :key="key" cols="12" lg="12">
          <h4 class="primary--text">{{ STATISTICS_ENUM[key] }}</h4>
          <bar-chart
            :chart-key="key"
            :chart-data="chartData(key)"
            :options="options"
            class="mx-2"
          />
        </v-col>
      </template>
    </v-row>
  </div>
</template>

<script>
import { DATA_GRIDS } from "@/services/enums/dataGridEnums"
import BarChart from "@/components/commons/charts/BarChart.vue"
import { mapActions, mapGetters } from "vuex"
import { TOPOLOGIES } from "@/store/modules/topologies/types"
import { REQUESTS_STATE } from "@/store/modules/api/types"
import { API } from "@/api"
import QuickFiltersMixin from "@/services/mixins/QuickFiltersMixin.vue"
import prettyMilliseconds from "pretty-ms"
import QuickGridFilter from "@/components/commons/grid/filter/QuickGridFilter.vue"

export default {
  mixins: [QuickFiltersMixin],
  name: "StatisticTab",
  components: {
    QuickGridFilter,
    BarChart,
  },
  data() {
    return {
      STATISTICS_ENUM: {
        totalsErrors: this.$t("page.status.totalsErrors"),
        queue_depth: this.$t("page.status.queue_depth"),
        waiting_time: this.$t("page.status.waiting_time"),
        process_time: this.$t("page.status.process_time"),
        cpu_time: this.$t("page.status.cpu_time"),
        request_time: this.$t("page.status.request_time"),
        process: this.$t("page.status.process"),
      },
      nodeParameters: [],
      DATA_GRIDS,
      nodes: [],
      filter: [],
      filterMeta: { index: 0 },
      options: {
        plugins: {
          title: {
            display: false,
          },
        },
        responsive: true,
        scales: {
          x: {
            stacked: true,
          },
          y: {
            stacked: false,
            ticks: {
              beginAtZero: true,
            },
          },
        },
      },
      headers: [
        {
          text: "timestamp",
          value: "timestamp",
          align: "left",
          sortable: true,
          visible: true,
        },
        {
          text: "correlation_id",
          value: "correlation_id",
          align: "left",
          sortable: true,
          visible: true,
        },
        {
          text: "node_id",
          value: "node_id",
          align: "left",
          sortable: true,
          visible: true,
        },
        {
          text: "node_name",
          value: "node_name",
          align: "left",
          sortable: true,
          visible: true,
        },
        {
          text: "severity",
          value: "severity",
          align: "left",
          sortable: true,
          visible: true,
        },
      ],
    }
  },
  computed: {
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    ...mapGetters(TOPOLOGIES.NAMESPACE, {
      topologyActiveNodes: TOPOLOGIES.GETTERS.GET_ACTIVE_TOPOLOGY_NODES,
      topologyActive: TOPOLOGIES.GETTERS.GET_ACTIVE_TOPOLOGY,
      topologyActiveStatistics:
        TOPOLOGIES.GETTERS.GET_ACTIVE_TOPOLOGY_STATISTICS,
    }),
    state() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE]([
        API.statistic.grid.id,
        API.statistic.getList.id,
      ])
    },
  },
  methods: {
    ...mapActions(TOPOLOGIES.NAMESPACE, [
      TOPOLOGIES.ACTIONS.DATA.GET_STATISTICS,
      TOPOLOGIES.ACTIONS.TOPOLOGY.NODES,
    ]),
    async onFilterChange(filter, filterMeta) {
      this.filter = filter
      this.filterMeta = filterMeta

      await this[TOPOLOGIES.ACTIONS.DATA.GET_STATISTICS]({
        id: this.topologyActive._id,
        settings: {
          filter,
        },
      })

      this.initNodes()
    },
    prettyMs: prettyMilliseconds,
    initNodes() {
      if (
        !this.topologyActiveStatistics.items ||
        !this.topologyActiveNodes ||
        !this.topologyActive
      ) {
        return
      }
      let keys = Object.keys(this.topologyActiveStatistics.items)
      let nodeKeys = keys.filter((node) =>
        Object.prototype.hasOwnProperty.call(
          this.topologyActiveStatistics.items[node],
          "queue_depth",
        ),
      )
      this.nodes = nodeKeys.map((node) => ({
        data: { ...this.topologyActiveStatistics.items[node] },
        name: node,
      }))

      let parameters = []
      this.nodes.forEach((node) => {
        parameters.push(
          ...Object.keys(node.data).filter(
            (it) => it !== "queue_depth" && it !== "process",
          ),
        )
      })
      this.nodeParameters = [...new Set(parameters)]
    },
    chartDataByKey(key, values) {
      let keyedData = []
      values.forEach((value) => {
        if (value && !isNaN(value[key])) {
          keyedData.push(value[key])
        }
      })
      return keyedData
    },
    chartData(key) {
      let labels = []
      let data = []
      this.nodes.forEach((node) => {
        if (node.data[key]) {
          data.push(node.data[key])
          if (
            this.topologyActiveNodes.filter((name) => name._id === node.name)[0]
          ) {
            labels.push(
              this.topologyActiveNodes.filter(
                (name) => name._id === node.name,
              )[0].name,
            )
          }
        }
      })

      // let prettifiedData = data.map((subject) => {
      //   for (let subjectKey in subject) {
      //     if (!Number.isNaN(Number.parseFloat(subject[subjectKey]))) {
      //       subject[subjectKey] = this.prettyMs(Number.parseInt(subject[subjectKey]), {
      //         keepDecimalsOnWholeSeconds: true,
      //       })
      //     }
      //   }
      //
      //   return subject
      // })
      //
      // console.log(prettifiedData)
      return {
        labels: labels,
        datasets: [
          {
            label: "min",
            backgroundColor: "#0e7d00",
            data: this.chartDataByKey("min", data),
            barPercentage: 0.5,
          },
          {
            label: "avg",
            backgroundColor: "#f7b500",
            data: this.chartDataByKey("avg", data),
            barPercentage: 0.5,
          },
          {
            label: "max",
            backgroundColor: "#cc0000",
            data: this.chartDataByKey("max", data),
            barPercentage: 0.5,
          },
        ],
      }
    },
    chartDataProcess(key) {
      let labels = []
      let data = []
      this.nodes.forEach((node) => {
        data.push(node.data[key])
        if (
          this.topologyActiveNodes.filter((name) => name._id === node.name)[0]
        ) {
          labels.push(
            this.topologyActiveNodes.filter((name) => name._id === node.name)[0]
              .name,
          )
        }
      })

      return {
        labels: labels,
        datasets: [
          {
            label: "errors",
            backgroundColor: "#cc0000",
            data: this.chartDataByKey("errors", data),
            barPercentage: 0.5,
          },
          {
            label: "total",
            backgroundColor: "#0e7d00",
            data: this.chartDataByKey("total", data),
            barPercentage: 0.5,
          },
        ],
      }
    },
  },
  async mounted() {
    this.initRuns("timestamp")

    await this[TOPOLOGIES.ACTIONS.TOPOLOGY.NODES](this.topologyActive._id)
    await this[TOPOLOGIES.ACTIONS.DATA.GET_STATISTICS]({
      id: this.topologyActive._id,
      settings: {
        filter: this.quickFilters[0].filter,
      },
    })
    this.initNodes()
  },
}
</script>
