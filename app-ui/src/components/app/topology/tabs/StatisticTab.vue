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
            <h4 class="body-2 primary--text">{{ STATISTICS_ENUM.totalsErrors }}</h4>
            <bar-chart :chart-data="chartDataProcess('process')" :options="options" class="mx-2" />
          </v-col>
          <template v-for="key in nodeParameters">
            <v-col :key="key" cols="12" lg="12">
              <h4 class="body-2 primary--text">{{ STATISTICS_ENUM[key] }}</h4>
              <bar-chart :chart-key="key" :chart-data="chartData(key)" :options="options" class="mx-2" />
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
import prettyMilliseconds from 'pretty-ms'

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
        plugins: {
          title: {
            display: false,
          },
          tooltip: {
            enabled: false,
            position: 'nearest',
            external: this.externalTooltipHandler,
          },
        },
        responsive: true,
        scales: {
          x: {
            stacked: true,
          },
          y: {
            stacked: true,
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
    ...mapState(TOPOLOGIES.NAMESPACE, ['nodeNames', 'topology']),
    ...mapState(DATA_GRIDS.STATISTICS, ['items']),
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    state() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE]([API.statistic.grid.id, API.statistic.getList.id])
    },
    nodeParameters() {
      return Object.keys(this.nodes[0].data).filter((it) => it !== 'queue_depth' && it !== 'process')
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
    getOrCreateTooltip(chart) {
      let tooltipEl = chart.canvas.parentNode.querySelector('div')

      if (!tooltipEl) {
        tooltipEl = document.createElement('div')
        tooltipEl.style.background = 'rgba(0, 0, 0, 0.7)'
        tooltipEl.style.borderRadius = '3px'
        tooltipEl.style.color = 'white'
        tooltipEl.style.opacity = 1
        tooltipEl.style.pointerEvents = 'none'
        tooltipEl.style.position = 'absolute'
        tooltipEl.style.transform = 'translate(-50%, 50%)'
        tooltipEl.style.transition = 'all .3s ease'

        const table = document.createElement('table')
        table.style.margin = '0px'

        tooltipEl.appendChild(table)
        chart.canvas.parentNode.appendChild(tooltipEl)
      }

      return tooltipEl
    },

    externalTooltipHandler(context) {
      // Tooltip Element
      const { chart, tooltip } = context
      const tooltipEl = this.getOrCreateTooltip(chart)

      // Hide if no tooltip
      if (tooltip.opacity === 0) {
        tooltipEl.style.opacity = 0
        return
      }

      // Set Text
      if (tooltip.body) {
        const titleLines = tooltip.title || []
        const bodyLines = tooltip.body.map((b) => b.lines)

        const tableHead = document.createElement('thead')

        titleLines.forEach((title) => {
          const tr = document.createElement('tr')
          tr.style.borderWidth = 0

          const th = document.createElement('th')
          th.style.borderWidth = 0
          const text = document.createTextNode(title)

          th.appendChild(text)
          tr.appendChild(th)
          tableHead.appendChild(tr)
        })

        const tableBody = document.createElement('tbody')
        bodyLines.forEach((body, i) => {
          const colors = tooltip.labelColors[i]

          const span = document.createElement('span')
          span.style.background = colors.backgroundColor
          span.style.borderColor = colors.borderColor
          span.style.borderWidth = '2px'
          span.style.marginRight = '10px'
          span.style.height = '10px'
          span.style.width = '10px'
          span.style.display = 'inline-block'

          const tr = document.createElement('tr')
          tr.style.backgroundColor = 'inherit'
          tr.style.borderWidth = 0

          const td = document.createElement('td')
          td.style.borderWidth = 0

          const bodyFormatted = body.map((line) => {
            let prefix = line.split(' ')[0]
            let prettifiedValue = this.prettyMs(Number.parseInt(line.split(' ')[1]), {
              keepDecimalsOnWholeSeconds: true,
            })
            return prefix.concat(` ${prettifiedValue}`)
          })

          const text = document.createTextNode(bodyFormatted)

          td.appendChild(span)
          td.appendChild(text)
          tr.appendChild(td)
          tableBody.appendChild(tr)
        })

        const tableRoot = tooltipEl.querySelector('table')

        // Remove old children
        while (tableRoot.firstChild) {
          tableRoot.firstChild.remove()
        }

        // Add new children
        tableRoot.appendChild(tableHead)
        tableRoot.appendChild(tableBody)
      }

      const { offsetLeft: positionX, offsetTop: positionY } = chart.canvas

      // Display, position, and set styles for font
      tooltipEl.style.opacity = 1
      tooltipEl.style.left = positionX + tooltip.caretX + 'px'
      tooltipEl.style.top = positionY + tooltip.caretY + 'px'
      tooltipEl.style.font = tooltip.options.bodyFont.string
      tooltipEl.style.padding = tooltip.options.padding + 'px ' + tooltip.options.padding + 'px'
    },

    prettyMs: prettyMilliseconds,
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
          },
          {
            label: 'avg',
            backgroundColor: '#f7b500',
            data: this.chartDataByKey('avg', data),
          },
          {
            label: 'max',
            backgroundColor: '#cc0000',
            data: this.chartDataByKey('max', data),
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
          },
          {
            label: 'total',
            backgroundColor: '#0e7d00',
            data: this.chartDataByKey('total', data),
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
