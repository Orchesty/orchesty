<template>
  <content-basic v-if="topologyActive" :title="`${topologyActive.name} v.${topologyActive.version}`">
    <bpmn-editor ref="editor" @isSending="isSending" />
    <unsaved-editor-modal ref="dialog" :is-sending="state.isSending" :get-saving-result="getSavingResult" />
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

export default {
  name: 'EditorPage',
  mixins: [ImportTopologyMixin],
  components: { ActionButtons, UnsavedEditorModal, BpmnEditor, ContentBasic },
  data() {
    return {
      ROUTES,
      sending: false,
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
    ]),
    async getSavingResult() {
      return await this.$refs.editor.saveDiagram()
    },
    async exportDiagram() {
      await this.$refs.editor.exportDiagram()
    },
    importDiagram(e) {
      this.fetchTopologyDiagram(e, null, true)
    },
    async saveDiagram() {
      const newTopologyId = await this.$refs.editor.saveDiagram()
      if (newTopologyId) {
        await this[TOPOLOGIES.ACTIONS.TOPOLOGY.GET_BY_ID](newTopologyId)
        await this[TOPOLOGIES.ACTIONS.DATA.GET_TOPOLOGIES]()
        await redirectTo(this.$router, { name: ROUTES.TOPOLOGY.VIEWER, params: { id: newTopologyId } })
      } else {
        await this[TOPOLOGIES.ACTIONS.TOPOLOGY.GET_DIAGRAM](this.topologyActive._id)
      }
    },
    async routeBack() {
      if (this.$refs.editor.state.isSending) {
        this.$router.go(-1)
        return
      }
      const scheme = await this.$refs.editor.fetchSchema()
      if (scheme !== this.topologyActiveDiagram) {
        this.$refs.dialog.isOpen = true
      } else {
        this.$router.go(-1)
      }
    },
    isSending(val) {
      this.sending = val
    },
  },
}
</script>
