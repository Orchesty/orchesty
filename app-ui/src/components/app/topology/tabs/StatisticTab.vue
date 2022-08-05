<template>
  <div v-if="topologyActive._id">
    <v-row v-if="nodes[0]">
      <v-col cols="12" lg="12">
        <h4 class="primary--text">{{ STATISTICS_ENUM.totalsErrors }}</h4>
        <bar-chart :chart-data="chartDataProcess('process')" :options="options" class="mx-2" />
      </v-col>
      <template v-for="key in nodeParameters">
        <v-col :key="key" cols="12" lg="12">
          <h4 class="primary--text">{{ STATISTICS_ENUM[key] }}</h4>
          <bar-chart :chart-key="key" :chart-data="chartData(key)" :options="options" class="mx-2" />
        </v-col>
      </template>
    </v-row>
  </div>
</template>

<script>
import { DATA_GRIDS } from '@/services/enums/dataGridEnums'
import BarChart from '@/components/commons/charts/BarChart'
import { mapActions, mapGetters } from 'vuex'
import { TOPOLOGIES } from '@/store/modules/topologies/types'
import { REQUESTS_STATE } from '@/store/modules/api/types'
import { API } from '@/api'
import QuickFiltersMixin from '@/services/mixins/QuickFiltersMixin'
import prettyMilliseconds from 'pretty-ms'

export default {
  mixins: [QuickFiltersMixin],
  name: 'StatisticTab',
  components: {
    BarChart,
  },
  data() {
    return {
      STATISTICS_ENUM: {
        totalsErrors: 'Total / Error',
        queue_depth: 'Queue depth',
        waiting_time: 'Waiting time',
        process_time: 'Process time',
        cpu_time: 'CPU time',
        request_time: 'Request time',
        process: 'Process',
      },
      nodeParameters: [],
      DATA_GRIDS,
      nodes: [],
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
          text: 'timestamp',
          value: 'timestamp',
          align: 'left',
          sortable: true,
          visible: true,
        },
        {
          text: 'correlation_id',
          value: 'correlation_id',
          align: 'left',
          sortable: true,
          visible: true,
        },
        {
          text: 'node_id',
          value: 'node_id',
          align: 'left',
          sortable: true,
          visible: true,
        },
        {
          text: 'node_name',
          value: 'node_name',
          align: 'left',
          sortable: true,
          visible: true,
        },
        {
          text: 'severity',
          value: 'severity',
          align: 'left',
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
      topologyActiveStatistics: TOPOLOGIES.GETTERS.GET_ACTIVE_TOPOLOGY_STATISTICS,
    }),
    state() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE]([API.statistic.grid.id, API.statistic.getList.id])
    },
  },
  methods: {
    ...mapActions(TOPOLOGIES.NAMESPACE, [TOPOLOGIES.ACTIONS.DATA.GET_STATISTICS, TOPOLOGIES.ACTIONS.TOPOLOGY.NODES]),

    prettyMs: prettyMilliseconds,
    initNodes() {
      if (!this.topologyActiveStatistics.items || !this.topologyActiveNodes || !this.topologyActive) {
        return
      }
      let keys = Object.keys(this.topologyActiveStatistics.items)
      let nodeKeys = keys.filter((node) =>
        Object.prototype.hasOwnProperty.call(this.topologyActiveStatistics.items[node], 'queue_depth')
      )
      this.nodes = nodeKeys.map((node) => ({ data: { ...this.topologyActiveStatistics.items[node] }, name: node }))

      let parameters = []
      this.nodes.forEach((node) => {
        parameters.push(...Object.keys(node.data).filter((it) => it !== 'queue_depth' && it !== 'process'))
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
          if (this.topologyActiveNodes.filter((name) => name._id === node.name)[0]) {
            labels.push(this.topologyActiveNodes.filter((name) => name._id === node.name)[0].name)
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
            label: 'min',
            backgroundColor: '#0e7d00',
            data: this.chartDataByKey('min', data),
            barPercentage: 0.5,
          },
          {
            label: 'avg',
            backgroundColor: '#f7b500',
            data: this.chartDataByKey('avg', data),
            barPercentage: 0.5,
          },
          {
            label: 'max',
            backgroundColor: '#cc0000',
            data: this.chartDataByKey('max', data),
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
        if (this.topologyActiveNodes.filter((name) => name._id === node.name)[0]) {
          labels.push(this.topologyActiveNodes.filter((name) => name._id === node.name)[0].name)
        }
      })

      return {
        labels: labels,
        datasets: [
          {
            label: 'errors',
            backgroundColor: '#cc0000',
            data: this.chartDataByKey('errors', data),
            barPercentage: 0.5,
          },
          {
            label: 'total',
            backgroundColor: '#0e7d00',
            data: this.chartDataByKey('total', data),
            barPercentage: 0.5,
          },
        ],
      }
    },
  },
  async mounted() {
    this.init('timestamp')
    await this[TOPOLOGIES.ACTIONS.TOPOLOGY.NODES](this.topologyActive._id)
    await this[TOPOLOGIES.ACTIONS.DATA.GET_STATISTICS]({ id: this.topologyActive._id })
    this.initNodes()
  },
}
</script>
