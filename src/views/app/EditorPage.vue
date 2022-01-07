<template>
  <content-basic v-if="topology" :title="`${topology.name} v.${topology.version}`">
    <bpmn-editor ref="editor" @isSending="isSending" @initialScheme="setInitialScheme" />
    <unsaved-editor-modal ref="dialog" :is-sending="state.isSending" :get-saving-result="getSavingResult" />
    <template slot="nav-buttons">
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
import { mapGetters, mapState } from 'vuex'
import { TOPOLOGIES } from '@/store/modules/topologies/types'
import { REQUESTS_STATE } from '@/store/modules/api/types'
import { API } from '@/api'
import UnsavedEditorModal from '@/components/app/bpmn/modals/UnsavedEditorModal'
import ActionButtons from '@/components/app/bpmn/components/ActionButtons'
import router from '@/services/router'

export default {
  name: 'EditorPage',
  components: { ActionButtons, UnsavedEditorModal, BpmnEditor, ContentBasic },
  data() {
    return {
      ROUTES,
      sending: false,
      initialScheme: '',
    }
  },
  computed: {
    ...mapState(TOPOLOGIES.NAMESPACE, ['topology']),
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
    async getSavingResult() {
      return await this.$refs.editor.saveDiagram()
    },
    async exportDiagram() {
      await this.$refs.editor.exportDiagram()
    },
    importDiagram(e) {
      this.$refs.editor.importDiagram(e)
    },
    async saveDiagram() {
      const isNewTopology = await this.$refs.editor.saveDiagram()
      if (isNewTopology) {
        await router.push({ name: ROUTES.TOPOLOGY.VIEWER, params: { id: isNewTopology } })
      }
    },
    async routeBack() {
      if (this.$refs.editor.state.isSending) {
        this.$router.go(-1)
        return
      }
      const scheme = await this.$refs.editor.fetchSchema()
      if (scheme.xml !== this.initialScheme.xml) {
        this.$refs.dialog.isOpen = true
      } else {
        this.$router.go(-1)
      }
    },
    setInitialScheme(initialScheme) {
      this.initialScheme = initialScheme
    },
    isSending(val) {
      this.sending = val
    },
  },
}
</script>
