<template>
  <v-row v-if="!state.isSending">
    <v-col cols="12">
      <v-card outlined>
        <div class="modeler d-flex">
          <div id="canvas-edit"></div>
          <div class="canvas-properties">
            <div id="properties"></div>
            <div id="properties2"></div>
            <div v-if="selectedShape === 'start'" class="mx-3 subtitle-2">
              <hr class="mb-2 mt-3" />
              <span class="font-weight-bold">{{ $t('topologies.editor.startingPoint') }}: </span>
              <div>{{ startingPoint }}</div>
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
import { mapActions, mapGetters, mapState } from 'vuex'
import { TOPOLOGIES } from '@/store/modules/topologies/types'
import { AUTH } from '@/store/modules/auth/types'
import { config } from '@/config'
import { IMPLEMENTATIONS } from '@/store/modules/implementations/types'
import { REQUESTS_STATE } from '@/store/modules/api/types'
import { API } from '@/api'
import { fitIntoScreen } from './helper'
import ProgressBarLinear from '@/components/commons/progressIndicators/ProgressBarLinear'
import FlashMessageMixin from '@/components/commons/mixins/FlashMessageMixin'

export default {
  name: 'BpmnIOEditor',
  components: { ProgressBarLinear },
  mixins: [FlashMessageMixin],
  data() {
    return {
      isSending: false,
      nodeDetail: '',
      selectedShape: '',
      startingPoint: '',
      modeler: null,
      propertiesPanel: null,
      initialScheme: null,
      topology: JSON.parse(localStorage.getItem('topology')),
    }
  },
  computed: {
    ...mapState(TOPOLOGIES.NAMESPACE, ['statistics']),
    ...mapState(TOPOLOGIES.NAMESPACE, ['nodeNames']),
    ...mapState(AUTH.NAMESPACE, ['user']),
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    state() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE]([
        API.topology.getNodes.id,
        API.topology.getTopologyNodes.id,
        API.implementation.getList.id,
        API.statistic.getList.id,
      ])
    },
  },
  methods: {
    ...mapActions(TOPOLOGIES.NAMESPACE, [
      TOPOLOGIES.ACTIONS.TOPOLOGY.NODES,
      TOPOLOGIES.ACTIONS.TOPOLOGY.GET_DIAGRAM,
      TOPOLOGIES.ACTIONS.DATA.GET_STATISTICS,
      TOPOLOGIES.ACTIONS.DATA.GET_SDK_NODES,
      TOPOLOGIES.ACTIONS.TOPOLOGY.SAVE_DIAGRAM,
    ]),
    ...mapActions(IMPLEMENTATIONS.NAMESPACE, [IMPLEMENTATIONS.ACTIONS.LIST_IMPLEMENTATIONS]),
    getNodeRunUrl(baseURL, nodeId, nodeName, nodeType, topologyId, topologyName, userId, data = {}) {
      return nodeType === 'webhook'
        ? `${baseURL}/topologies/${topologyName}/nodes/${nodeName}/token/${data.token ? data.token : 'token'}/run`
        : `${baseURL}/topologies/${topologyId}/nodes/${nodeId}/user/${userId}/run`
    },
    importDiagram(e) {
      const reader = new FileReader()
      const file = e.target.files[0]
      reader.onload = async (response) => {
        await this.openBPMN({ file, content: response.target.result })
      }
      reader.readAsText(file)
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
    async fetchSchema() {
      return await this.modeler.saveXML({ format: true })
    },
    async openBPMN(data) {
      if (this.modeler) {
        try {
          await this.modeler.importXML(data.content)
          this.showFlashMessage(false, 'Topology imported')
        } catch (err) {
          console.log(err.message, err.warnings)
          this.showFlashMessage(true, err.message)
        }
      }
    },
    async saveDiagram() {
      if (this.modeler) {
        const result = await this.modeler.saveXML({ format: true })
        this.initialScheme = result
        this.$emit('initialScheme', result)
        const { xml } = result

        const parser = new DOMParser()
        let xmlDoc = parser.parseFromString(xml, 'text/xml')
        console.log(xmlDoc.getElementsByTagName('bpmn:task'))
        for (let i = 0; i < xmlDoc.getElementsByTagName('bpmn:task').length; i++) {
          for (let j = 0; j < xmlDoc.getElementsByTagName('bpmn:task')[i].attributes.length; j++) {
            if (xmlDoc.getElementsByTagName('bpmn:task')[i].attributes[j].name === 'sdkHostOptions') {
              xmlDoc.getElementsByTagName('bpmn:task')[i].removeAttribute('sdkHostOptions')
            }
          }
        }

        return await this[TOPOLOGIES.ACTIONS.TOPOLOGY.SAVE_DIAGRAM]({
          id: this.topology._id,
          xml: new XMLSerializer().serializeToString(xmlDoc),
        }).then(async (response) => {
          if (response._id !== this.topology._id) {
            return response._id
          } else {
            return false
          }
        })
      } else {
        return false
      }
    },
    async exportDiagram() {
      if (this.modeler) {
        try {
          const result = await this.modeler.saveXML({ format: true })
          const { xml } = result
          download(xml, `${this.topology.name}.v${this.topology.version}` + '.tplg', 'application/bpmn+xml')
          this.showFlashMessage(false, `Topology ${this.topology.name} exported`)
        } catch (err) {
          this.showFlashMessage(true, err.response.data.message)
        }
      }
    },
    async initAsync() {
      await this[TOPOLOGIES.ACTIONS.DATA.GET_SDK_NODES]()
      await this[TOPOLOGIES.ACTIONS.TOPOLOGY.NODES]({ id: this.topology._id })
      await this[IMPLEMENTATIONS.ACTIONS.LIST_IMPLEMENTATIONS]()
      await this[TOPOLOGIES.ACTIONS.DATA.GET_STATISTICS]({
        id: this.topology._id,
        settings: {},
      })
    },
  },
  async mounted() {
    await this.initAsync()
    let diagram = await this[TOPOLOGIES.ACTIONS.TOPOLOGY.GET_DIAGRAM]({ topologyID: this.topology._id })
    this.modeler = new Modeler({
      additionalModules: [
        PropertiesPanelModule,
        {
          __init__: ['propertiesProvider', 'customRenderer', 'contextPadProvider', 'elementFactory', 'paletteProvider'],
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
    })
    this.propertiesPanel = this.modeler.get('propertiesPanel')
    fitIntoScreen(this.modeler, diagram, [110, 20])

    this.modeler.get('eventBus').on('shape.added', (event) => {
      //get sdk service names
      let sdkHosts111 = JSON.parse(localStorage.getItem('pipes'))
      let sdkHosts
      if (!sdkHosts111) {
        return
      } else {
        sdkHosts = sdkHosts111.items
      }
      if (!event.element.businessObject.get('sdkHost') && sdkHosts[0]) {
        // console.info('SDK SERVICE NAMES: FETCHED')

        //set sdkHost in businessObject to default value if not present to prevent exception
        event.element.businessObject.set('sdkHost', sdkHosts[0].url)
        // console.error(`BUSINESS OBJECT LACKS SDK, DEFAULT SET: ${event.element.businessObject.get('sdkHost')}`)
      } else {
        // console.info(`BUSINESS OBJECT CONTAINS SDK: ${event.element.businessObject.get('sdkHost')}`)
      }

      //set sdkHostServices in businessObject to default value if not present to prevent exception
      if (event.element.businessObject.get('sdkHost')) {
        event.element.businessObject.set(
          'sdkHostName',
          sdkHosts.filter((item) => item.url === event.element.businessObject.get('sdkHost'))[0].name
        )
      }
      //get type of the node
      const pipesType = event.element.businessObject.pipesType
      // console.info(`NODE TYPE: ${pipesType}`)

      //get all sdk services available
      const sdkHostServices = JSON.parse(localStorage.getItem('pipes-nodes-list'))
      // console.info(`SDK SERVICES: ${JSON.stringify(sdkHostServices)}`)

      //get name of the used SDK
      const sdkServiceName = event.element.businessObject.$attrs.sdkHostName
      // console.info(`NODE SDK NAME: ${sdkServiceName}`)

      //check if node sdk name has service options for the provided pipes type
      if (sdkHostServices[sdkServiceName] && sdkHostServices[sdkServiceName][pipesType]) {
        const options = sdkHostServices[sdkServiceName][pipesType].map((item) => ({
          name: item,
          value: item,
        }))
        event.element.businessObject.set('sdkHostOptions', options)
        // console.info(`NODE SDK SERVICE OPTIONS SIZE: ${options.length}`)
      } else {
        // console.error(`NODE DOES NOT HAVE SERVICE OPTIONS`)
      }
    })

    this.modeler.get('eventBus').on('shape.changed', (event) => {
      if (!event.gfx.querySelector('.djs-label')) {
        return
      }
      const label = event.gfx.querySelector('.djs-label')

      this.centerNodeName(label)

      //get sdk service names
      let sdkHosts111 = JSON.parse(localStorage.getItem('pipes'))
      let sdkHosts
      if (!sdkHosts111) {
        return
      } else {
        sdkHosts = sdkHosts111.items
      }
      console.info('SDK SERVICE NAMES: FETCHED')

      //set sdkHost in businessObject to default value if not present to prevent exception
      if (!event.element.businessObject.get('sdkHost') && sdkHosts[0]) {
        event.element.businessObject.set('sdkHost', sdkHosts[0].url)
        console.error(`BUSINESS OBJECT LACKS SDK, DEFAULT SET: ${event.element.businessObject.get('sdkHost')}`)
      } else {
        console.info(`BUSINESS OBJECT CONTAINS SDK: ${event.element.businessObject.get('sdkHost')}`)
      }

      //set sdkHostServices in businessObject to default value if not present to prevent exception
      if (event.element.businessObject.get('sdkHost')) {
        event.element.businessObject.set(
          'sdkHostName',
          sdkHosts.filter((item) => item.url === event.element.businessObject.get('sdkHost'))[0].name
        )
      }

      //get type of the node
      const pipesType = event.element.businessObject.pipesType
      console.info(`NODE TYPE: ${pipesType}`)

      //get all sdk services available
      const sdkHostServices = JSON.parse(localStorage.getItem('pipes-nodes-list'))
      console.info(`SDK SERVICES: ${JSON.stringify(sdkHostServices)}`)

      //get name of the used SDK
      const sdkServiceName = event.element.businessObject.$attrs.sdkHostName
      console.info(`NODE SDK NAME: ${sdkServiceName}`)

      //check if node sdk name has service options for the provided pipes type
      console.log(sdkHostServices[sdkServiceName], sdkHostServices[sdkServiceName][pipesType])
      if (sdkHostServices[sdkServiceName] && sdkHostServices[sdkServiceName][pipesType]) {
        const options = sdkHostServices[sdkServiceName][pipesType].map((item) => ({
          name: item,
          value: item,
        }))
        event.element.businessObject.set('sdkHostOptions', options)
        console.info(`NODE SDK SERVICE OPTIONS SIZE: ${options.length}`)
      } else {
        console.error(`NODE DOES NOT HAVE SERVICE OPTIONS`)
      }

      console.log(this.modeler.get('propertiesPanel'))
      document.querySelector(`[data-element-id = ${event.element.id}]`)
      this.modeler.get('propertiesPanel')
      const select = document.querySelector('#camunda-name-select')
      if (select) {
        select.innerHTML = ''
        console.log(select)
        if (event.element.businessObject.$attrs?.sdkHostOptions) {
          event.element.businessObject.$attrs.sdkHostOptions.forEach((item) => {
            let option = document.createElement('option')
            option.value = option.text = item.name
            select.add(option)
          })
        }
      }
    })

    this.modeler.get('eventBus').on('selection.changed', (event) => {
      if (event.newSelection[0]) {
        this.selectedShape = event.newSelection[0].businessObject.pipesType
        const node = Object.values(this.nodeNames).filter((n) => {
          return n.schema_id === event.newSelection[0].id
        })[0]
        if (node) {
          this.selectedShape = node.type
          this.startingPoint = this.getNodeRunUrl(
            config.backend.apiStartingPoint,
            node._id,
            node.name,
            node.type,
            node.topology_id,
            this.topology.name,
            this.user.user.id
          )
        }
      } else {
        this.selectedShape = ''
      }
    })
    try {
      await this.modeler.importXML(diagram)
      const initialScheme = await this.modeler.saveXML({ format: true })
      this.initialScheme = initialScheme
      this.$emit('initialScheme', initialScheme)
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
