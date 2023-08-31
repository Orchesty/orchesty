<template>
  <content-basic v-if="topologyActive" :title="`${topologyActive.name} v.${topologyActive.version}`">
    <bpmn-editor ref="editor" />
    <unsaved-editor-modal
      ref="dialog"
      :redirect-function="redirectFunction"
      :is-sending="state.isSending"
      :save-has-new-id="saveHasNewId"
    />
    <template #nav-buttons>
      <action-buttons
        :route-back="routeBack"
        :export-diagram="exportDiagram"
        :set-file-input="importDiagram"
        :save-diagram="saveDiagram"
        :is-sending="state.isSending"
      />
    </template>
  </content-basic>
</template>

<script>
import { ROUTES } from '@/services/enums/routerEnums'
import ContentBasic from '../../components/layout/content/ContentBasic'
import BpmnEditor from '@/components/app/bpmn/BpmnEditor'
import { mapActions, mapGetters } from 'vuex'
import { TOPOLOGIES } from '@/store/modules/topologies/types'
import { REQUESTS_STATE } from '@/store/modules/api/types'
import { API } from '@/api'
import UnsavedEditorModal from '@/components/app/bpmn/modals/UnsavedEditorModal'
import ActionButtons from '@/components/app/bpmn/components/ActionButtons'
import { redirectTo } from '@/services/utils/utils'
import ImportTopologyMixin from '@/services/mixins/ImportTopologyMixin'
import { EVENTS, events } from '@/services/utils/events'

export default {
  name: 'EditorPage',
  mixins: [ImportTopologyMixin],
  components: { ActionButtons, UnsavedEditorModal, BpmnEditor, ContentBasic },
  data() {
    return {
      ROUTES,
      sending: false,
      redirectFunction: () => {},
    }
  },
  computed: {
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    ...mapGetters(TOPOLOGIES.NAMESPACE, {
      topologyActive: TOPOLOGIES.GETTERS.GET_ACTIVE_TOPOLOGY,
      topologyActiveDiagram: TOPOLOGIES.GETTERS.GET_ACTIVE_TOPOLOGY_DIAGRAM,
    }),
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
      TOPOLOGIES.ACTIONS.TOPOLOGY.GET_BY_ID,
      TOPOLOGIES.ACTIONS.DATA.GET_TOPOLOGIES,
      TOPOLOGIES.ACTIONS.TOPOLOGY.GET_DIAGRAM,
      TOPOLOGIES.ACTIONS.TOPOLOGY.CHECK_DIAGRAM_CHANGED,
    ]),
    async saveHasNewId() {
      return await this.$refs.editor.saveDiagram()
    },
    async exportDiagram() {
      await this.$refs.editor.exportDiagram()
    },
    importDiagram(e) {
      this.fetchTopologyDiagram(e, null, true)
    },
    async saveDiagram() {
      const newId = await this.saveHasNewId()
      if (newId) {
        await this[TOPOLOGIES.ACTIONS.TOPOLOGY.GET_BY_ID](newId)
        await this[TOPOLOGIES.ACTIONS.DATA.GET_TOPOLOGIES]()
        await redirectTo(this.$router, {
          name: ROUTES.TOPOLOGY.VIEWER,
          params: { id: newId, forceRoute: true },
        })
      } else {
        await this[TOPOLOGIES.ACTIONS.TOPOLOGY.GET_DIAGRAM](this.topologyActive._id)
      }
    },
    routeBack() {
      this.$router.go(-1)
    },
  },
  mounted() {
    events.listen(EVENTS.EDITOR.COMPARE_XML, async (redirectFunction) => {
      this.redirectFunction = redirectFunction
      const xml = new XMLSerializer().serializeToString(await this.$refs.editor.getCurrentXMLDiagram())

      const diagramChanged = await this[TOPOLOGIES.ACTIONS.TOPOLOGY.CHECK_DIAGRAM_CHANGED]({
        xml,
        id: this.$refs.editor.topologyActive._id,
      })

      if (diagramChanged) {
        this.$refs.dialog.isOpen = true
      } else {
        this.redirectFunction()
      }
    })
  },
}
</script>
