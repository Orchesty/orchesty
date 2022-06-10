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
    nodeParameters() {
      return Object.keys(this.nodes[0].data).filter((it) => it !== 'queue_depth' && it !== 'process')
    },
  },
  methods: {
    ...mapActions(TOPOLOGIES.NAMESPACE, [TOPOLOGIES.ACTIONS.DATA.GET_STATISTICS, TOPOLOGIES.ACTIONS.TOPOLOGY.NODES]),
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
            if (!(prefix.includes('error') || prefix.includes('total'))) {
              let prettifiedValue = this.prettyMs(Number.parseFloat(line.split(' ')[1]), {
                formatSubMilliseconds: true,
              })
              return prefix.concat(` ${prettifiedValue}`)
            } else {
              return line
            }
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
      if (!this.topologyActiveStatistics.items || !this.topologyActiveNodes || !this.topologyActive) {
        return
      }
      let keys = Object.keys(this.topologyActiveStatistics.items)
      let nodeKeys = keys.filter((node) =>
        Object.prototype.hasOwnProperty.call(this.topologyActiveStatistics.items[node], 'queue_depth')
      )
      this.nodes = nodeKeys.map((node) => ({ data: { ...this.topologyActiveStatistics.items[node] }, name: node }))
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
        if (this.topologyActiveNodes.filter((name) => name._id === node.name)[0]) {
          labels.push(this.topologyActiveNodes.filter((name) => name._id === node.name)[0].name)
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
