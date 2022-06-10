<template>
  <div>
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
    <v-row v-if="!state.isSending" key="2" dense class="d-flex justify-start">
      <div class="ml-4 mt-1 canvas--checkboxes">
        <v-checkbox
          v-model="showErrors"
          dense
          :label="$t('topologies.viewer.errorsToggle')"
          hide-details
          color="error"
          @click="toggleOverlaysHandler"
        >
          <template #label>
            <span class="body-2">{{ $t('topologies.viewer.errorsToggle') }}</span>
          </template>
        </v-checkbox>
        <v-checkbox
          v-model="showUserTasks"
          dense
          :label="$t('topologies.viewer.userTasksToggle')"
          hide-details
          color="primary"
          @click="toggleOverlaysHandler"
        >
          <template #label>
            <span class="body-2">{{ $t('topologies.viewer.userTasksToggle') }}</span>
          </template>
        </v-checkbox>
        <v-checkbox
          v-model="showQueue"
          dense
          hide-details
          :label="$t('topologies.viewer.queueDepthToggle')"
          color="secondary"
          @click="toggleOverlaysHandler"
        >
          <template #label>
            <span class="body-2">{{ $t('topologies.viewer.queueDepthToggle') }}</span>
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
            <span class="body-2">{{ $t('topologies.viewer.testResultsToggle') }}</span>
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
import PipesModdleDescriptor from '../../bpmn/bpnmConfig/descriptors/pipes.json'
import CustomRenderer from '@/components/app/bpmn/bpnmConfig/customModules/CustomRenderer'
import CustomContextPadProvider from '@/components/app/bpmn/bpnmConfig/customModules/CustomContextPadProvider'
import CustomElementFactory from '@/components/app/bpmn/bpnmConfig/customModules/CustomElementFactory'
import CustomPalette from '@/components/app/bpmn/bpnmConfig/customModules/CustomPalette'
import Modeler from 'bpmn-js/lib/Modeler'
import { mapActions, mapGetters } from 'vuex'
import { TOPOLOGIES } from '@/store/modules/topologies/types'
import { fitIntoScreen } from '@/components/app/bpmn/helper'
import { REQUESTS_STATE } from '@/store/modules/api/types'
import { API } from '@/api'
import { OPERATOR } from '@/services/enums/gridEnums'
import { DATA_GRIDS } from '@/services/enums/dataGridEnums'
import { USER_TASKS } from '@/store/modules/userTasks/types'
import BpmnNodeGrid from '@/components/app/bpmn/components/BpmnNodeGrid'
import QuickGridFilter from '@/components/commons/grid/filter/QuickGridFilter'
import ProgressBarLinear from '@/components/commons/progressIndicators/ProgressBarLinear'
import QuickFiltersMixin from '@/services/mixins/QuickFiltersMixin'
import { EVENTS, events } from '@/services/utils/events'
import { QUICK_FILTERS } from '@/services/utils/quickFilters'

export default {
  name: 'ViewerTab',
  components: { ProgressBarLinear, QuickGridFilter, BpmnNodeGrid },
  mixins: [QuickFiltersMixin],
  data() {
    return {
      DATA_GRIDS,
      viewer: null,
      testResultsShow: false,
      showErrors: true,
      showUserTasks: true,
      showQueue: true,
      showTest: false,
      startEventTypes: ['start', 'webhook', 'cron'],
      selectedNode: {},
      overlays: [],
      filterMeta: {},
      filter: [],
    }
  },
  computed: {
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    ...mapGetters(USER_TASKS.NAMESPACE, { userTasks: USER_TASKS.GETTERS.GET_USER_TASKS }),
    ...mapGetters(TOPOLOGIES.NAMESPACE, {
      topologyActive: TOPOLOGIES.GETTERS.GET_ACTIVE_TOPOLOGY,
      topologyActiveNodes: TOPOLOGIES.GETTERS.GET_ACTIVE_TOPOLOGY_NODES,
      topologyActiveStatistics: TOPOLOGIES.GETTERS.GET_ACTIVE_TOPOLOGY_STATISTICS,
      topologyActiveDiagram: TOPOLOGIES.GETTERS.GET_ACTIVE_TOPOLOGY_DIAGRAM,
    }),
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
    diagramOptions() {
      return {
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
      }
    },
  },
  methods: {
    ...mapActions(TOPOLOGIES.NAMESPACE, [
      TOPOLOGIES.ACTIONS.TOPOLOGY.NODES,
      TOPOLOGIES.ACTIONS.TOPOLOGY.GET_DIAGRAM,
      TOPOLOGIES.ACTIONS.DATA.GET_STATISTICS,
    ]),
    ...mapActions(USER_TASKS.NAMESPACE, [USER_TASKS.ACTIONS.USER_TASK_FETCH_TASKS]),

    //#1 BACKEND DATA FETCH
    async initData(topologyActive) {
      await Promise.all([
        this[TOPOLOGIES.ACTIONS.TOPOLOGY.NODES](topologyActive._id),
        this[TOPOLOGIES.ACTIONS.TOPOLOGY.GET_DIAGRAM](topologyActive._id),
        this[TOPOLOGIES.ACTIONS.DATA.GET_STATISTICS]({ id: topologyActive._id, settings: null }),
        this[USER_TASKS.ACTIONS.USER_TASK_FETCH_TASKS]({
          filter: [[{ column: 'topologyId', operator: OPERATOR.EQUAL, value: topologyActive._id }]],
        }),
      ]).then(async () => await this.prepareCanvas(topologyActive))
    },

    //#2 PREPARE THE CANVAS
    async prepareCanvas(topologyActive) {
      window.orchestyIndex = null
      this.canvasReset()
      this.viewer = new Modeler(this.diagramOptions)

      //disable doubleclick editing
      this.viewer.on('element.dblclick', 1500, function (event) {
        event.stopPropagation()
      })

      //set the variable for the canvas overlays and error + queue counter
      this.overlays = this.viewer.get('overlays')
      let errors = ''
      let queueDepth = ''

      //on shape added action
      this.viewer.on('shape.added', (event) => {
        let element = event.element

        //ignore flow arrows
        if (element.labelTarget || !element.businessObject.$instanceOf('bpmn:FlowNode')) {
          return
        }

        //get currently processed node object
        const node = Object.values(this.topologyActiveNodes).filter((n) => {
          return n.schema_id === element.businessObject.id
        })[0]

        //get node metrics if exists
        if (this.topologyActiveStatistics.items[node._id]) {
          errors = this.topologyActiveStatistics.items[node._id].process.errors
          queueDepth = this.topologyActiveStatistics.items[node._id].queue_depth.avg
        }

        //if UserTask - draw a UserTask overlay
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

        //if not Starting Point - draw metrics overlay
        if (!this.isStartingPoint(element)) {
          window.orchestyIndex = node._id
          if (errors !== '0') {
            this.overlays.add(element, 'bubbles', {
              position: { top: element.height - 10, right: element.width - 5 },
              html: `<div><span data-index="${window.orchestyIndex}" class="badge badge-error" title="Failed processes">${errors}</span></div>`,
            })
          }
          this.overlays.add(element, 'bubbles', {
            position: { top: element.height - 10, right: element.width - 15 },
            html: `<div onclick="window.dispatchEvent(new CustomEvent('queueDepthCheckbox'))"><span class="badge badge-queue" title="Queue depth">${Math.round(
              queueDepth
            )}</span></div>`,
          })
        }

        //if Starting Point - add enable/disable functionality
        if (this.isStartingPoint(element)) {
          window.orchestyIndex = node._id
          this.overlays.add(element, {
            position: {
              top: 0,
              left: 0,
            },
            html: `<div title="${node.enabled ? 'enabled' : 'disabled'}" class="starting-point" data-index="${
              window.orchestyIndex
            }" style="height: ${element.height}px;
              width: ${element.width}px"></div>`,
          })
        }
      })

      try {
        await this.importXMLDiagram()
      } catch (err) {
        console.log(err)
      } finally {
        this.hasTests(topologyActive)
        this.showTestResults(topologyActive)
        this.hookEventListeners()
        this.toggleOverlaysHandler()
        this.setStartingPointsColor()

        let labels = document.querySelectorAll('.djs-label')
        labels.forEach((label) => {
          this.centerNodeName(label)
        })

        this.selectedNode = {}
      }
    },
    //#3 IMPORT XML DIAGRAM
    async importXMLDiagram() {
      await this.viewer.importXML(this.topologyActiveDiagram)
      fitIntoScreen(this.viewer, this.topologyActiveDiagram)
    },

    //FILTER HANDLERS
    onFilterChange(filter, filterMeta) {
      this.filter = filter
      this.filterMeta = filterMeta
    },
    onFilterClear() {
      this.filter = [
        [
          {
            column: 'updated',
            operator: OPERATOR.BETWEEN,
            value: QUICK_FILTERS.LAST_HOUR(),
            isQuickFilter: true,
          },
        ],
      ]
      this.filterMeta = {}
    },

    //HANDLERS
    toggleOverlaysHandler() {
      this.visibilitySwitcher(this.showErrors, '.badge-error')
      this.visibilitySwitcher(this.showUserTasks, '.badge-tasks')
      this.visibilitySwitcher(this.showQueue, '.badge-queue')
    },
    enableStartingPointHandler(e) {
      let selectedNode = this.topologyActiveNodes.filter((nodeName) => {
        if (e?.detail?.dataset?.index) {
          return nodeName._id === e.detail.dataset.index
        } else {
          return false
        }
      })[0]
      events.emit(EVENTS.MODAL.NODE.UPDATE, selectedNode)
    },
    nodeSelectionHandler(e) {
      this.selectedNode = this.topologyActiveNodes.filter((nodeName) => {
        if (e?.detail?.dataset?.index) {
          return nodeName._id === e.detail.dataset.index
        } else {
          return false
        }
      })[0]
    },

    //RESET HANDLERS
    closeLogs() {
      this.selectedNode = {}
    },
    canvasReset() {
      if (!document.querySelector('#canvas')) {
        return
      }
      document.querySelector('#canvas').innerHTML = ''
    },
    async reload() {},

    //HOOK EVENT LISTENERS
    hookEventListeners() {
      document.querySelectorAll('.badge-error').forEach((badgeError) => {
        badgeError.addEventListener('click', function () {
          window.dispatchEvent(new CustomEvent('nodeSelection', { detail: badgeError }))
        })
      })
      document.querySelectorAll('.starting-point').forEach((startingPoint) => {
        startingPoint.addEventListener('click', function () {
          window.dispatchEvent(new CustomEvent('enableStartingPoint', { detail: startingPoint }))
        })
      })
    },

    //VISIBILITY SWITCHER
    visibilitySwitcher(condition, className) {
      document
        .querySelectorAll(className)
        .forEach((error) => (!condition ? (error.style['display'] = 'none') : (error.style['display'] = 'block')))
    },

    //CONDITIONS CHECKER
    isStartingPoint(element) {
      return this.startEventTypes.some((excludedName) => element.businessObject.pipesType === excludedName)
    },
    hasTests(topology) {
      this.showTest = !!topology.test
      this.testResultsShow = topology.test ? Object.keys(topology.test).length === 0 : true
    },

    //COLORING
    setStartingPointsColor() {
      this.topologyActiveNodes.forEach((node) => {
        if (node.type === 'start' || node.type === 'cron' || node.type === 'webhook') {
          let svg = document.querySelectorAll(`g[data-element-id='${node.schema_id}'] .djs-visual > *:not(text)`)
          svg.forEach((svg) => {
            !node.enabled ? (svg.style.fill = '#D11818') : (svg.style.fill = '#66C600')
          })
        }
      })
    },
    showTestResults(topology) {
      if (topology.test) {
        this.topologyActiveNodes.forEach((node) => {
          topology.test.forEach((test) => {
            if (test.id === node._id) {
              let svg = document.querySelectorAll(`g[data-element-id='${node.schema_id}'] .djs-visual > *:not(text)`)
              if (test.status !== 'ok') {
                svg.forEach((svg) => {
                  this.showTest ? (svg.style.fill = '#D11818') : (svg.style.fill = '#ffffff')
                })
              }
            }
          })
        })
      }
    },

    //LABEL CENTERING
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
  },
  watch: {
    quickFilters: {
      deep: true,
      handler(val) {
        if (val.length === 5) {
          this.filter = [
            [
              {
                column: 'timestamp',
                operator: OPERATOR.BETWEEN,
                value: QUICK_FILTERS.LAST_HOUR(),
                isQuickFilter: true,
              },
            ],
          ]
        }
      },
    },
    topologyActive: {
      deep: true,
      immediate: true,
      async handler(topologyActive) {
        await this.initData(topologyActive)
      },
    },
  },
  created() {
    window.addEventListener('nodeSelection', this.nodeSelectionHandler)
    window.addEventListener('errorCheckbox', this.toggleOverlaysHandler)
    window.addEventListener('userTasksCheckbox', this.toggleOverlaysHandler)
    window.addEventListener('queueDepthCheckbox', this.toggleOverlaysHandler)
    window.addEventListener('enableStartingPoint', this.enableStartingPointHandler)
  },
  mounted() {
    this.init('timestamp')
  },
  destroyed() {
    window.removeEventListener('nodeSelection', this.nodeSelectionHandler)
    window.removeEventListener('errorCheckbox', this.toggleOverlaysHandler)
    window.removeEventListener('userTasksCheckbox', this.toggleOverlaysHandler)
    window.removeEventListener('queueDepthCheckbox', this.toggleOverlaysHandler)
    window.removeEventListener('enableStartingPoint', this.enableStartingPointHandler)
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
.starting-point {
  cursor: pointer;
  //content: url('../../../../assets/svg/play-circle-outline.svg');
  transform: scale(0.7);
}
</style>
