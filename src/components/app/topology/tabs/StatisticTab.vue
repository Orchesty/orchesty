<template>
  <div v-if="topology._id">
    <data-grid
      is-iterator
      :headers="headers"
      :namespace="DATA_GRIDS.STATISTICS"
      disable-headers
      :quick-filters="quickFilters"
      :is-loading="state.isSending"
      disable-search
      disable-pagination
      :request-params="{ id: topology._id }"
      disabled-advanced-filter
      @reset="initNodes"
    >
      <template #default>
        <v-row v-if="nodes[0]">
          <v-col cols="12" lg="12">
            <h4 class="ml-3 mb-2 disabled--text text--darken-3">{{ STATISTICS_ENUM.totalsErrors }}</h4>
            <bar-chart :chart-data="chartDataProcess('process')" :options="options" :height="80" class="mx-2" />
          </v-col>
          <template v-for="key in nodeParameters">
            <v-col :key="key" cols="12" lg="12">
              <h4 class="ml-3 mb-2 disabled--text text--darken-3">{{ STATISTICS_ENUM[key] }}</h4>
              <bar-chart :chart-data="chartData(key)" :options="options" :height="80" class="mx-2" />
            </v-col>
          </template>
        </v-row>
      </template>
    </data-grid>
  </div>
</template>

<script>
import { DATA_GRIDS } from '@/services/enums/dataGridEnums'
import BarChart from '@/components/commons/charts/BarChart'
import { mapGetters, mapState } from 'vuex'
import { TOPOLOGIES } from '@/store/modules/topologies/types'
import DataGrid from '@/components/commons/table/DataGrid'
import { REQUESTS_STATE } from '@/store/modules/api/types'
import { API } from '@/api'
import QuickFiltersMixin from '@/components/commons/mixins/QuickFiltersMixin'

export default {
  mixins: [QuickFiltersMixin],
  name: 'StatisticTab',
  components: {
    DataGrid,
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
      DATA_GRIDS,
      nodes: [],
      options: {
        title: {
          display: false,
        },
        tooltips: {
          mode: 'index',
          intersect: false,
        },
        responsive: true,
        scales: {
          xAxes: [
            {
              id: 'bar-x-axis1',
              categoryPercentage: 0.1,
              offset: true,
              ticks: {
                beginAtZero: true,
              },
            },
            {
              display: false,
              id: 'bar-x-axis2',
              categoryPercentage: 0.1,
              offset: true,
              ticks: {
                beginAtZero: true,
              },
            },
            {
              display: false,
              id: 'bar-x-axis3',
              categoryPercentage: 0.1,
              offset: true,
              ticks: {
                beginAtZero: true,
              },
            },
          ],
          yAxes: [
            {
              ticks: {
                beginAtZero: true,
              },
            },
          ],
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
    ...mapState(TOPOLOGIES.NAMESPACE, ['nodeNames', 'topology']),
    ...mapState(DATA_GRIDS.STATISTICS, ['items']),
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    state() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE]([API.statistic.grid.id, API.statistic.getList.id])
    },
    nodeParameters() {
      const data = Object.keys(this.nodes[0].data).filter((it) => it !== 'queue_depth')
      // 0"waiting_time", 1"process_time", 2"cpu_time", 3"request_time", 4"process" ]
      return [data[4], data[1], data[3], data[2], data[0]]
    },
  },
  watch: {
    items: {
      deep: true,
      handler() {
        this.initNodes()
      },
    },
    nodeNames: {
      deep: true,
      handler() {
        this.initNodes()
      },
    },
    topology: {
      deep: true,
      handler() {
        this.initNodes()
      },
    },
  },
  methods: {
    initNodes() {
      if (!this.items[0] || !this.nodeNames || !this.topology) {
        return
      }
      let keys = Object.keys(this.items[0])
      let nodeKeys = keys.filter((node) => Object.prototype.hasOwnProperty.call(this.items[0][node], 'queue_depth'))
      this.nodes = nodeKeys.map((node) => ({ data: { ...this.items[0][node] }, name: node }))
    },
    chartDataByKey(key, values) {
      let keyedData = []
      values.forEach((value) => {
        if (!isNaN(value[key])) {
          keyedData.push(value[key])
        }
      })
      return keyedData
    },
    chartData(key) {
      let labels = []
      let data = []
      this.nodes.forEach((node) => {
        data.push(node.data[key])
        if (this.nodeNames.filter((name) => name._id === node.name)[0]) {
          labels.push(this.nodeNames.filter((name) => name._id === node.name)[0].name)
        }
      })
      return {
        labels: labels,
        datasets: [
          {
            label: 'min',
            backgroundColor: '#0e7d00',
            data: this.chartDataByKey('min', data),
            xAxisID: 'bar-x-axis1',
          },
          {
            label: 'avg',
            backgroundColor: '#f7b500',
            data: this.chartDataByKey('avg', data),
            xAxisID: 'bar-x-axis2',
          },
          {
            label: 'max',
            backgroundColor: '#cc0000',
            data: this.chartDataByKey('max', data),
            xAxisID: 'bar-x-axis3',
          },
        ],
      }
    },
    chartDataProcess(key) {
      let labels = []
      let data = []
      this.nodes.forEach((node) => {
        data.push(node.data[key])
        if (this.nodeNames.filter((name) => name._id === node.name)[0]) {
          labels.push(this.nodeNames.filter((name) => name._id === node.name)[0].name)
        }
      })

      return {
        labels: labels,
        datasets: [
          {
            label: 'errors',
            backgroundColor: '#cc0000',
            data: this.chartDataByKey('errors', data),
            xAxisID: 'bar-x-axis1',
            ticks: {
              beginAtZero: true,
            },
          },
          {
            label: 'total',
            backgroundColor: '#0e7d00',
            data: this.chartDataByKey('total', data),
            xAxisID: 'bar-x-axis2',
            ticks: {
              beginAtZero: true,
            },
          },
        ],
      }
    },
  },
  mounted() {
    this.init('timestamp')
  },
}
</script>
