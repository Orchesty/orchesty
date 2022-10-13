<template>
  <v-row v-if="!state.isSending">
    <v-col cols="12">
      <v-card outlined>
        <div class="modeler d-flex">
          <div id="canvas-edit"></div>
          <div class="canvas-properties">
            <div id="properties"></div>
            <div v-if="isStartingPoint" class="mx-3 subtitle-2">
              <hr class="mb-2 mt-3" />
              <span class="font-weight-bold">{{ $t('topologies.editor.startingPoint') }}: </span>
              <div>{{ startingPointMessage }}</div>
            </div>
          </div>
        </div>
      </v-card>
    </v-col>
  </v-row>
  <v-row v-else key="1">
    <v-col cols="12" class="canvas-loader d-flex flex-column align-center justify-center">
      <progress-bar-linear />
      <h4 class="font-weight-medium mt-5">{{ $t('enums.loading.editor') }}</h4>
    </v-col>
  </v-row>
</template>

<script>
import Modeler from 'bpmn-js/lib/Modeler'
import camundaModdleDescriptor from 'camunda-bpmn-moddle/resources/camunda'
import PropertiesPanelModule from 'bpmn-js-properties-panel'
import PropertiesProviderModule from './bpnmConfig/PropertiesPanel/PropertiesProvider'
import PipesModdleDescriptor from './bpnmConfig/descriptors/pipes.json'
import CustomContextPadProvider from './bpnmConfig/customModules/CustomContextPadProvider'
import CustomElementFactory from './bpnmConfig/customModules/CustomElementFactory'
import CustomRenderer from './bpnmConfig/customModules/CustomRenderer'
import CustomPalette from './bpnmConfig/customModules/CustomPalette'
import download from '@/services/utils/download'
import { mapActions, mapGetters } from 'vuex'
import { TOPOLOGIES } from '@/store/modules/topologies/types'
import { AUTH } from '@/store/modules/auth/types'
import { config } from '@/config'
import { IMPLEMENTATIONS } from '@/store/modules/implementations/types'
import { REQUESTS_STATE } from '@/store/modules/api/types'
import { API } from '@/api'
import ProgressBarLinear from '@/components/commons/progressIndicators/ProgressBarLinear'
import FlashMessageMixin from '@/services/mixins/FlashMessageMixin'
import { LOCAL_STORAGE } from '@/services/enums/localStorageEnums'

export default {
  name: 'BpmnIOEditor',
  components: { ProgressBarLinear },
  mixins: [FlashMessageMixin],
  data() {
    return {
      firstFetchDone: false,
      selectedShape: null,
      startingPoint: null,
      modeler: null,
    }
  },
  computed: {
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    ...mapGetters(AUTH.NAMESPACE, { userId: AUTH.GETTERS.GET_LOGGED_USER_ID }),
    ...mapGetters(TOPOLOGIES.NAMESPACE, {
      topologyActiveNodes: TOPOLOGIES.GETTERS.GET_ACTIVE_TOPOLOGY_NODES,
      topologyActive: TOPOLOGIES.GETTERS.GET_ACTIVE_TOPOLOGY,
      topologyActiveDiagram: TOPOLOGIES.GETTERS.GET_ACTIVE_TOPOLOGY_DIAGRAM,
    }),
    state() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE]([
        API.topology.getNodes.id,
        API.topology.getTopologyNodes.id,
        API.implementation.getList.id,
      ])
    },
    isStartingPoint() {
      return this.selectedShape?.businessObject?.pipesType === 'start'
    },
    startingPointMessage() {
      return this.startingPoint ?? this.$t('topologies.editor.noStartingPointFound')
    },
    modelerOptions() {
      return {
        additionalModules: [
          PropertiesPanelModule,
          {
            __init__: [
              'propertiesProvider',
              'customRenderer',
              'contextPadProvider',
              'elementFactory',
              'paletteProvider',
            ],
          },
          { propertiesProvider: ['type', PropertiesProviderModule] },
          { customRenderer: ['type', CustomRenderer] },
          { contextPadProvider: ['type', CustomContextPadProvider] },
          { elementFactory: ['type', CustomElementFactory] },
          { paletteProvider: ['type', CustomPalette] },
        ],
        container: '#canvas-edit',
        propertiesPanel: {
          parent: '#properties',
        },
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
      TOPOLOGIES.ACTIONS.DATA.GET_SDK_NODES,
      TOPOLOGIES.ACTIONS.TOPOLOGY.SAVE_DIAGRAM,
    ]),
    ...mapActions(IMPLEMENTATIONS.NAMESPACE, [IMPLEMENTATIONS.ACTIONS.LIST_IMPLEMENTATIONS]),

    async getCurrentXMLDiagram() {
      const parser = new DOMParser()
      const { xml } = await this.modeler.saveXML({ format: true })

      let xmlNodes = parser.parseFromString(xml, 'text/xml')
      for (let i = 0; i < xmlNodes.getElementsByTagName('bpmn:task').length; i++) {
        for (let j = 0; j < xmlNodes.getElementsByTagName('bpmn:task')[i].attributes.length; j++) {
          if (xmlNodes.getElementsByTagName('bpmn:task')[i].attributes[j].name === 'sdkHostOptions') {
            xmlNodes.getElementsByTagName('bpmn:task')[i].removeAttribute('sdkHostOptions')
          }
        }
      }

      return xmlNodes
    },

    setNewStartingPoint() {
      if (this.selectedShape) {
        const topologyNodes = Object.values(this.topologyActiveNodes)

        const node = topologyNodes.filter((node) => {
          return node.schema_id === this.selectedShape.id
        })

        if (node[0]) {
          this.startingPoint = this.getNodeRunUrl(
            config.backend.apiStartingPoint,
            node[0]._id,
            node[0].name,
            node[0].type,
            node[0].topology_id,
            this.topologyActive.name,
            this.userId
          )
        } else {
          this.startingPoint = null
        }
      }
    },

    getNodeRunUrl(baseURL, nodeId, nodeName, nodeType, topologyId, topologyName, userId, data = {}) {
      return nodeType === 'webhook'
        ? `${baseURL}/topologies/${topologyName}/nodes/${nodeName}/token/${data.token ? data.token : 'token'}/run`
        : `${baseURL}/topologies/${topologyId}/nodes/${nodeId}/user/${userId}/run`
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

    async initData() {
      await Promise.all([
        this[IMPLEMENTATIONS.ACTIONS.LIST_IMPLEMENTATIONS](),
        this[TOPOLOGIES.ACTIONS.DATA.GET_SDK_NODES](),
        this[TOPOLOGIES.ACTIONS.TOPOLOGY.NODES](this.topologyActive._id),
        this[TOPOLOGIES.ACTIONS.TOPOLOGY.GET_DIAGRAM](this.topologyActive._id),
      ]).then(async () => {
        await this.prepareCanvas()
        this.firstFetchDone = true
      })
    },

    async prepareCanvas() {
      this.modeler = new Modeler(this.modelerOptions)

      this.modeler.on('shape.added', (event) => {
        //get sdk service names
        let implementations = JSON.parse(localStorage.getItem(LOCAL_STORAGE.IMPLEMENTATIONS)).items
        if (!implementations) {
          return
        }

        //get type of the node
        const pipesType = event.element.businessObject.pipesType

        //get all sdk services available
        const sdkHostServices = JSON.parse(localStorage.getItem(LOCAL_STORAGE.SDK_OPTIONS))

        //get name of the used SDK
        const sdkServiceName =
          implementations.filter((implementation) => implementation.url === event.element.businessObject.sdkHost)[0]
            ?.name || null

        //check if node sdk name has service options for the provided pipes type
        if (sdkHostServices[sdkServiceName] && sdkHostServices[sdkServiceName][pipesType]) {
          const options = sdkHostServices[sdkServiceName][pipesType].map((item) => ({
            name: item,
            value: item,
          }))
          event.element.businessObject.set('sdkHostOptions', [''].concat(options))
        }

        const select = document.querySelector('#camunda-name-select')
        if (select) {
          select.innerHTML = ''
          if (event.element.businessObject.$attrs?.sdkHostOptions) {
            event.element.businessObject.$attrs.sdkHostOptions.forEach((item) => {
              let option = document.createElement('option')
              option.value = option.text = item.name ?? ''
              select.add(option)
            })
          }
        }
      })

      this.modeler.on('shape.changed', (event) => {
        if (!event.gfx.querySelector('.djs-label')) {
          return
        }
        const label = event.gfx.querySelector('.djs-label')

        this.centerNodeName(label)

        //get sdk service names
        let implementations = JSON.parse(localStorage.getItem(LOCAL_STORAGE.IMPLEMENTATIONS)).items
        if (!implementations) {
          return
        }

        //set sdkHost in businessObject to default value if not present to prevent exception
        if (!event.element.businessObject.get('sdkHost') && implementations[0]) {
          event.element.businessObject.set('sdkHost', implementations[0].url)
        }

        //get type of the node
        const pipesType = event.element.businessObject.pipesType

        //get all sdk services available
        const sdkHostServices = JSON.parse(localStorage.getItem(LOCAL_STORAGE.SDK_OPTIONS))

        //get name of the used SDK
        const sdkServiceName = implementations.filter(
          (implementation) => implementation.url === event.element.businessObject.sdkHost
        )[0].name

        if (event.element.businessObject.get('sdkHost')) {
          event.element.businessObject.set('sdkHostName', sdkServiceName)
        }

        //check if node sdk name has service options for the provided pipes type
        if (sdkHostServices[sdkServiceName] && sdkHostServices[sdkServiceName][pipesType]) {
          const options = sdkHostServices[sdkServiceName][pipesType].map((item) => ({
            name: item,
            value: item,
          }))
          event.element.businessObject.set('sdkHostOptions', [''].concat(options))
        }

        const select = document.querySelector('#camunda-name-select')
        if (select) {
          select.innerHTML = ''
          if (event.element.businessObject.$attrs?.sdkHostOptions) {
            event.element.businessObject.$attrs.sdkHostOptions.forEach((item) => {
              let option = document.createElement('option')
              option.value = option.text = item.name ?? ''
              select.add(option)
            })
          }
        }
      })

      this.modeler.on('selection.changed', (event) => {
        if (event.newSelection[0]) {
          this.selectedShape = event.newSelection[0]
          this.setNewStartingPoint()
        } else {
          this.selectedShape = null
        }
      })
      try {
        await this.modeler.importXML(this.topologyActiveDiagram)
      } catch (err) {
        console.log(err.message, err.warnings)
        this.showFlashMessage(true, err)
      } finally {
        let labels = document.querySelectorAll('.djs-label')
        labels.forEach((label) => {
          this.centerNodeName(label)
        })
      }
    },

    async compareDiagrams() {
      return this.topologyActiveDiagram === new XMLSerializer().serializeToString(await this.getCurrentXMLDiagram())
    },

    async importXMLDiagram(xml) {
      if (this.modeler) {
        try {
          await this.modeler.importXML(xml)
        } catch (err) {
          console.log(err.message, err.warnings)
          this.showFlashMessage(true, err.message)
        }
      }
    },

    hasNewId(response) {
      if (response._id !== this.topologyActive._id) {
        return response._id
      } else {
        return false
      }
    },

    async saveDiagram() {
      if (this.modeler) {
        const xml = new XMLSerializer().serializeToString(await this.getCurrentXMLDiagram())

        return await this[TOPOLOGIES.ACTIONS.TOPOLOGY.SAVE_DIAGRAM]({
          id: this.topologyActive._id,
          xml,
        }).then(async (response) => {
          await this.initData()
          return this.hasNewId(response)
        })
      } else {
        return false
      }
    },

    async exportDiagram() {
      if (this.modeler) {
        try {
          const xml = await this.getCurrentXMLDiagram()

          download(
            new XMLSerializer().serializeToString(xml),
            `${this.topologyActive.name}.v${this.topologyActive.version}` + '.tplg',
            'application/bpmn+xml'
          )
          this.showFlashMessage(false, `Topology ${this.topologyActive.name} exported`)
        } catch (err) {
          this.showFlashMessage(true, err.response.data.message)
        }
      }
    },
  },
  watch: {
    topologyActiveDiagram: {
      async handler(diagram) {
        if (this.firstFetchDone) {
          await this.importXMLDiagram(diagram)
        }
      },
    },
  },
  async mounted() {
    await this.initData()
  },
}
</script>
<style lang="scss">
.canvas-properties {
  width: 20%;
  position: relative;
  border-bottom-left-radius: 0.4em;
  border-top-left-radius: 0.4em;
  border-left: 1px solid #ebebeb;
  //box-shadow: 0 0 5px 3px #ebebeb;
}

.canvas-loader {
  height: 80vh;
}

#canvas-edit {
  pointer-events: all;
  height: 80vh;
  width: 80%;
}
#canvas-edit .djs-visual,
#canvas-edit .djs-outline {
  pointer-events: all;
}
#canvas-edit .djs-element > .djs-hit-stroke,
#canvas-edit .djs-element > .djs-hit-click-stroke {
  pointer-events: all;
}
#canvas-edit svg {
  pointer-events: all;
}
#camunda-repeaterEnabled,
#camunda-userTaskGroup {
  margin-right: 7px;
  margin-bottom: 3px;
}
.bpmn-icon-pipes-connector-batch {
  margin: 12px 9px 6px 9px !important;
}
.djs-palette {
  max-width: 50px;
}
.djs-palette-entries .group {
  display: flex;
  flex-direction: column;
}
.djs-palette-entries .group .entry {
  margin: auto;
}
</style>
