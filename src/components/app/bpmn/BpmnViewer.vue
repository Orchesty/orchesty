<template>
  <div>
    <v-row dense>
      <v-col cols="12">
        <quick-grid-filter
          :quick-filters="quickFilters"
          :filter="filter"
          :filter-meta="filterMeta"
          :on-change="onFilterChange"
        >
          <template slot="buttonLeft">
            <v-btn color="primary" icon @click="reload">
              <v-icon> mdi-reload </v-icon>
            </v-btn>
          </template>
          <template slot="buttonRight">
            <v-btn text class="ml-auto" @click="onFilterClear">
              {{ $t('dataGrid.clear') }}
            </v-btn>
          </template>
        </quick-grid-filter>
      </v-col>
    </v-row>
    <v-row v-if="!state.isSending" key="2" dense class="d-flex justify-start">
      <div class="ml-4 mt-1 canvas--checkboxes">
        <v-checkbox
          v-model="showErrors"
          dense
          :label="$t('topologies.viewer.errorsToggle')"
          hide-details
          color="red"
          @click="toggleOverlays"
        >
          <template #label>
            <span class="subtitle-2">{{ $t('topologies.viewer.errorsToggle') }}</span>
          </template>
        </v-checkbox>
        <v-checkbox
          v-model="showUserTasks"
          dense
          :label="$t('topologies.viewer.userTasksToggle')"
          hide-details
          color="primary"
          @click="toggleOverlays"
        >
          <template #label>
            <span class="subtitle-2">{{ $t('topologies.viewer.userTasksToggle') }}</span>
          </template>
        </v-checkbox>
        <v-checkbox
          v-model="showQueue"
          dense
          hide-details
          :label="$t('topologies.viewer.queueDepthToggle')"
          color="secondary"
          @click="toggleOverlays"
        >
          <template #label>
            <span class="subtitle-2">{{ $t('topologies.viewer.queueDepthToggle') }}</span>
          </template>
        </v-checkbox>
        <v-checkbox
          v-model="showTest"
          :disabled="testResultsShow"
          dense
          hide-details
          :label="$t('topologies.viewer.testResultsToggle')"
          color="accent"
          @click="showTestResults"
        >
          <template #label>
            <span class="subtitle-2">{{ $t('topologies.viewer.testResultsToggle') }}</span>
          </template>
        </v-checkbox>
      </div>
      <v-col cols="12">
        <v-card outlined class="bpmn-viewer-node-grid-container">
          <div id="canvas" :class="canvasHeight"></div>
          <div v-if="Object.keys(selectedNode).length" class="bpmn-viewer-node-grid">
            <bpmn-node-grid :node="selectedNode" :filter="filter" @closeLogs="closeLogs" />
          </div>
        </v-card>
      </v-col>
    </v-row>
    <v-row v-else key="1">
      <v-col cols="12">
        <v-card outlined :class="canvasHeight" class="mt-3 d-flex flex-column align-center justify-center">
          <progress-bar-linear />
          <h4 class="font-weight-medium mt-5">{{ $t('enums.loading.editor') }}</h4>
        </v-card>
      </v-col>
    </v-row>
  </div>
</template>

<script>
import camundaModdleDescriptor from 'camunda-bpmn-moddle/resources/camunda'
import PipesModdleDescriptor from './bpnmConfig/descriptors/pipes.json'
import CustomRenderer from '@/components/app/bpmn/bpnmConfig/customModules/CustomRenderer'
import CustomContextPadProvider from '@/components/app/bpmn/bpnmConfig/customModules/CustomContextPadProvider'
import CustomElementFactory from '@/components/app/bpmn/bpnmConfig/customModules/CustomElementFactory'
import CustomPalette from '@/components/app/bpmn/bpnmConfig/customModules/CustomPalette'
import Modeler from 'bpmn-js/lib/Modeler'
import { mapActions, mapGetters, mapState } from 'vuex'
import { TOPOLOGIES } from '@/store/modules/topologies/types'
import { IMPLEMENTATIONS } from '@/store/modules/implementations/types'
import { fitIntoScreen } from './helper'
import { REQUESTS_STATE } from '@/store/modules/api/types'
import { API } from '@/api'
import { OPERATOR } from '@/store/grid'
import { DATA_GRIDS } from '@/store/grid/grids'
import { USER_TASKS } from '@/store/modules/userTasks/types'
import BpmnNodeGrid from '@/components/app/bpmn/components/BpmnNodeGrid'
import QuickGridFilter from '@/components/commons/table/filter/QuickGridFilter'
import ProgressBarLinear from '@/components/commons/progressIndicators/ProgressBarLinear'
import { DATE_FILTERS } from '@/services/filters'

export default {
  name: 'BpmnIOViewer',
  components: { ProgressBarLinear, QuickGridFilter, BpmnNodeGrid },
  props: {
    topology: {
      type: Object,
      required: true,
      default: () => {},
    },
  },
  data() {
    return {
      DATA_GRIDS,
      diagram: null,
      viewer: null,
      testResultsShow: false,
      showErrors: true,
      showUserTasks: true,
      showQueue: true,
      showTest: false,
      excludedNodeNames: ['start', 'webhook', 'cron'],
      selectedNode: {},
      overlays: [],
      filterMeta: {},
      filter: [],
      quickFilters: [
        {
          name: 'enums.duration.last5mins',
          filter: [
            [
              {
                column: 'timestamp',
                operator: OPERATOR.BETWEEN,
                value: DATE_FILTERS.LAST_5_MINS(),
              },
            ],
          ],
        },
        {
          name: 'enums.duration.last30mins',
          filter: [
            [
              {
                column: 'timestamp',
                operator: OPERATOR.BETWEEN,
                value: DATE_FILTERS.LAST_30_MINS(),
              },
            ],
          ],
        },
        {
          name: 'enums.duration.lastHour',
          filter: [
            [
              {
                column: 'timestamp',
                operator: OPERATOR.BETWEEN,
                value: DATE_FILTERS.LAST_HOUR(),
              },
            ],
          ],
        },
        {
          name: 'enums.duration.last6hours',
          filter: [
            [
              {
                column: 'timestamp',
                operator: OPERATOR.BETWEEN,
                value: DATE_FILTERS.LAST_6_HOURS(),
              },
            ],
          ],
        },
        {
          name: 'enums.duration.last24hours',
          filter: [
            [
              {
                column: 'timestamp',
                operator: OPERATOR.BETWEEN,
                value: DATE_FILTERS.LAST_24_HOURS(),
              },
            ],
          ],
        },
      ],
    }
  },
  computed: {
    ...mapState(TOPOLOGIES.NAMESPACE, ['statistics', 'nodeNames']),
    ...mapState(USER_TASKS.NAMESPACE, ['userTasks']),
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    state() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE]([
        API.topology.getNodes.id,
        API.implementation.getList.id,
        API.statistic.getList.id,
      ])
    },
    canvasHeight() {
      return 'canvas--basic'
    },
  },
  methods: {
    ...mapActions(TOPOLOGIES.NAMESPACE, [
      TOPOLOGIES.ACTIONS.TOPOLOGY.NODES,
      TOPOLOGIES.ACTIONS.TOPOLOGY.GET_DIAGRAM,
      TOPOLOGIES.ACTIONS.DATA.GET_STATISTICS,
    ]),
    ...mapActions(USER_TASKS.NAMESPACE, [USER_TASKS.ACTIONS.USER_TASK_FETCH_TASKS]),
    ...mapActions(IMPLEMENTATIONS.NAMESPACE, [IMPLEMENTATIONS.ACTIONS.LIST_IMPLEMENTATIONS]),
    onFilterChange(filter, filterMeta) {
      this.filter = filter
      this.filterMeta = filterMeta
    },
    onFilterClear() {
      this.filter = []
      this.filterMeta = {}
    },
    closeLogs() {
      this.selectedNode = {}
    },
    canvasReset() {
      document.querySelector('#canvas').innerHTML = ''
      window.orchestyIndex = null
    },
    toggleOverlays() {
      this.visibilitySwitcher(this.showErrors, '.badge-error')
      this.visibilitySwitcher(this.showUserTasks, '.badge-tasks')
      this.visibilitySwitcher(this.showQueue, '.badge-queue')
    },
    hookEventListeners() {
      document.querySelectorAll('.badge-error').forEach((badgeError) => {
        badgeError.addEventListener('click', function () {
          window.dispatchEvent(new CustomEvent('nodeSelection', { detail: badgeError }))
        })
      })
    },
    visibilitySwitcher(condition, className) {
      document
        .querySelectorAll(className)
        .forEach((error) => (!condition ? (error.style['display'] = 'none') : (error.style['display'] = 'block')))
    },
    testExists(topology) {
      this.showTest = !!topology.test
      this.testResultsShow = topology.test ? Object.keys(topology.test).length === 0 : true
    },
    nodeSelectionHandler(e) {
      this.selectedNode = this.nodeNames.filter((nodeName) => {
        if (e?.detail?.dataset?.index) {
          return nodeName._id === e.detail.dataset.index
        } else {
          return false
        }
      })[0]
    },
    showTestResults(topology) {
      if (topology.test) {
        this.nodeNames.forEach((node) => {
          topology.test.forEach((test) => {
            if (test.id === node._id) {
              let svg = document.querySelectorAll(`g[data-element-id='${node.schema_id}'] .djs-visual > *:not(text)`)
              if (test.status !== 'ok') {
                svg.forEach((svg) => {
                  this.showTest ? (svg.style.fill = 'rgba(255,40,44,0.53)') : (svg.style.fill = '#ffffff')
                })
              }
            }
          })
        })
      }
    },
    centerNodeName(label) {
      let name = ''
      const labelChildrenFinal = label.children.length
      for (let i = 0; i < label.children.length - 1; i++) {
        name += label.children.item(i).innerHTML
      }
      if (label.children.length > 3) {
        for (let i = labelChildrenFinal - 1; i >= 3; i--) {
          label.children.item(i).remove()
        }
      }
      if (label.children.length > 1) {
        for (let i = 0; i < label.children.length; i++) {
          label.children.item(i).setAttribute('y', `${i === 0 ? 32 : i === 1 ? 48 : 64}`)
        }
        label.children.item(label.children.length - 1).innerHTML =
          label.children.item(label.children.length - 1).innerHTML + '...'
      }

      const title = document.createElementNS('http://www.w3.org/2000/svg', 'title')
      title.textContent = name
      label.appendChild(title)
    },
    async reload() {
      await this.fetchStatistics(this.topology._id, this.filter).then(async () => await this.initBpmn(this.topology))
    },
    async initDiagram(topology) {
      this.diagram = await this[TOPOLOGIES.ACTIONS.TOPOLOGY.GET_DIAGRAM]({ topologyID: topology._id })
      await this.viewer.importXML(this.diagram)
      fitIntoScreen(this.viewer, this.diagram)
    },
    async initBpmn(topology) {
      this.canvasReset()
      this.viewer = new Modeler({
        additionalModules: [
          {
            zoomScroll: ['value', ''],
          },
          {
            __init__: ['customRenderer', 'contextPadProvider', 'elementFactory', 'paletteProvider'],
          },
          { customRenderer: ['type', CustomRenderer] },
          { contextPadProvider: ['type', CustomContextPadProvider] },
          { elementFactory: ['type', CustomElementFactory] },
          { paletteProvider: ['type', CustomPalette] },
        ],
        container: '#canvas',
        moddleExtensions: {
          pipes: PipesModdleDescriptor,
          camunda: camundaModdleDescriptor,
        },
      })
      this.overlays = this.viewer.get('overlays')

      this.viewer.get('eventBus').on('shape.added', (event) => {
        let element = event.element

        if (element.labelTarget || !element.businessObject.$instanceOf('bpmn:FlowNode')) {
          return
        }

        const node = Object.values(this.nodeNames).filter((n) => {
          return n.schema_id === element.businessObject.id
        })[0]

        if (element.businessObject.pipesType === 'user') {
          if (this.userTasks.items) {
            this.viewer.get('overlays').add(element, 'bubbles', {
              position: { top: element.height - 10, right: element.width - 30 },
              html:
                `<div onclick="window.dispatchEvent(new CustomEvent('userTasksCheckbox'))"><span class="badge badge-tasks" title="Waiting tasks">` +
                this.userTasks.items.filter((item) => item.nodeId === node._id).length +
                '</span></div>',
            })
          }
        }

        let errors = ''
        let queueDepth = ''

        if (this.statistics.items[node._id]) {
          errors = this.statistics.items[node._id].process.errors
        }

        if (this.statistics.items[node._id]) {
          queueDepth = this.statistics.items[node._id].queue_depth.avg
        }

        if (!this.excludedNodeNames.some((el) => element.businessObject.pipesType === el)) {
          window.orchestyIndex = node._id

          if (errors !== '0') {
            this.overlays.add(element, 'bubbles', {
              position: { top: element.height - 10, right: element.width - 5 },
              html: `<div><span data-index="${window.orchestyIndex}" class="badge badge-error" title="Failed processes">${errors}</span></div>`,
            })
          }

          this.overlays.add(element, 'bubbles', {
            position: { top: element.height + 10, right: element.width - 15 },
            html: `<div onclick="window.dispatchEvent(new CustomEvent('queueDepthCheckbox'))"><span class="badge badge-queue" title="Queue depth">${queueDepth}</span></div>`,
          })
        }
      })

      this.viewer.get('eventBus').on('element.dblclick', 1500, function (event) {
        event.stopPropagation()
      })

      try {
        await this.initDiagram(topology)
      } catch (err) {
        console.log(err)
      } finally {
        this.testExists(topology)
        this.showTestResults(topology)
        this.hookEventListeners()
        this.toggleOverlays()
        let labels = document.querySelectorAll('.djs-label')
        labels.forEach((label) => {
          this.centerNodeName(label)
        })
      }
    },
    async fetchStatistics(id, filter) {
      await this[TOPOLOGIES.ACTIONS.DATA.GET_STATISTICS]({
        id: id,
        settings: {
          filter: filter,
          sorter: null,
          paging: { itemsPerPage: 50, page: 1 },
          search: '',
        },
      })
    },
    async fetchUserTasks() {
      await this[USER_TASKS.ACTIONS.USER_TASK_FETCH_TASKS]({
        filter: [[{ column: 'topologyId', operator: OPERATOR.EQUAL, value: this.topology._id }]],
        sorter: null,
        paging: { itemsPerPage: 50, page: 1 },
        params: { id: this.topology._id },
        search: '',
      })
    },
    async initData(data) {
      await Promise.all([
        this[TOPOLOGIES.ACTIONS.TOPOLOGY.NODES]({ id: data._id }),
        this[IMPLEMENTATIONS.ACTIONS.LIST_IMPLEMENTATIONS]({ data: {} }),
        this.fetchStatistics(data._id, this.filter),
        this.fetchUserTasks(),
      ]).then(async () => await this.initBpmn(this.topology))
    },
  },
  watch: {
    topology: {
      deep: true,
      async handler(topology) {
        this.selectedNode = {}
        await this.initData(topology)
      },
    },
    filter: {
      deep: true,
      async handler(val) {
        await Promise.all([this.fetchStatistics(this.topology._id, val), this.fetchUserTasks()]).then(
          async () => await this.initBpmn(this.topology)
        )
      },
    },
  },
  created() {
    window.addEventListener('nodeSelection', this.nodeSelectionHandler)
    window.addEventListener('errorCheckbox', this.toggleOverlays)
    window.addEventListener('userTasksCheckbox', this.toggleOverlays)
    window.addEventListener('queueDepthCheckbox', this.toggleOverlays)
  },
  destroyed() {
    window.removeEventListener('nodeSelection', this.nodeSelectionHandler)
    window.removeEventListener('errorCheckbox', this.toggleOverlays)
    window.removeEventListener('userTasksCheckbox', this.toggleOverlays)
    window.removeEventListener('queueDepthCheckbox', this.toggleOverlays)
  },
}
</script>
<style lang="scss">
.bpmn-viewer-node-grid-container {
  padding-top: 35px;
  position: relative;
  .bpmn-viewer-node-grid {
    position: absolute;
    bottom: 0;
    z-index: 3;
    width: 100%;
  }
}
.canvas--checkboxes {
  position: absolute;
  z-index: 2;
  display: flex;
  flex-direction: row;
  gap: 20px;
}

#canvas {
  height: 70vh;
}
#canvas .djs-palette {
  display: none !important;
}
#canvas .djs-context-pad {
  display: none !important;
}
#canvas .djs-visual,
#canvas .djs-outline {
  pointer-events: none;
}
.djs-label {
  pointer-events: stroke;
}
.djs-visual > *:not(text) {
  transition: 0.3s all;
}
#canvas .djs-element > .djs-hit-stroke,
#canvas .djs-element > .djs-hit-click-stroke {
  pointer-events: none;
}
.djs-element > .djs-hit-all {
  pointer-events: none;
}
#canvas .djs-overlay-bubbles {
  width: 80px;
}
#canvas svg {
  pointer-events: all;
}
.badge {
  font-size: 0.7em;
  font-weight: 600;
  display: block;
  border-radius: 50%;
  width: 20px;
  line-height: 20px;
  height: 20px;
  text-align: center;
  color: white;
}
.badge-tasks {
  background-color: #1c2849;
}
.badge-error {
  cursor: pointer;
  background-color: rgba(255, 40, 44, 1);
}
.badge-queue {
  color: #339cb4;
  width: 40px;
  text-align: start;
}
</style>
