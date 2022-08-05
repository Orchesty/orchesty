<template>
  <modal-template
    v-model="isOpen"
    :title="$t('topologies.modals.import.title')"
    :on-close="onClose"
    :on-cancel="onClose"
    :on-confirm="submit"
  >
    <template v-if="implementationsFile && implementationsProject" #default>
      <v-row v-if="!setDiagramState">
        <v-col cols="12">
          <app-input
            v-model="altName"
            label="alter the topology name | left blank if not desired to"
            placeholder="alter the topology name | left blank if not desired to"
          />
        </v-col>
      </v-row>
      <v-row>
        <template v-for="file in implementationsFile">
          <v-col :key="file.name + Math.random()" cols="4" class="d-flex">
            <app-input label="diagram sdk value" :value="file.name" disabled />
          </v-col>
          <v-col :key="file.name + Math.random()" cols="8">
            <v-select
              v-model="file.replace"
              dense
              outlined
              label="change diagram sdk value with"
              placeholder="change diagram sdk value with"
              :items="implementationsProject"
              item-value="url"
              item-text="name"
            />
          </v-col>
        </template>
      </v-row>
    </template>
    <template #sendingButton>
      <app-button
        :sending-title="$t('button.sending.importing')"
        :is-sending="state.isSending"
        :flat="false"
        :button-title="$t('button.import')"
        :on-click="submit"
        :color="'primary'"
      />
    </template>
  </modal-template>
</template>

<script>
import { events, EVENTS } from '../../../../services/utils/events'
import ModalTemplate from '../../../commons/modal/ModalTemplate'
import { mapActions, mapGetters, mapState } from 'vuex'
import { REQUESTS_STATE } from '@/store/modules/api/types'
import { API } from '@/api'
import { IMPLEMENTATIONS } from '@/store/modules/implementations/types'
import ImportTopologyMixin from '@/services/mixins/ImportTopologyMixin'
import AppButton from '@/components/commons/button/AppButton'
import AppInput from '@/components/commons/input/AppInput'
import { TOPOLOGIES } from '@/store/modules/topologies/types'

export default {
  name: 'ModalImportTopology',
  components: { AppInput, AppButton, ModalTemplate },
  mixins: [ImportTopologyMixin],
  data: () => ({
    altName: '',
    folderId: null,
    isOpen: false,
    setDiagramState: false,
    implementationsProject: null,
    implementationsFile: null,
  }),
  computed: {
    ...mapState(IMPLEMENTATIONS.NAMESPACE, ['topologyImportState']),
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    state() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE]([API.implementation.getList.id])
    },
  },
  methods: {
    ...mapActions(IMPLEMENTATIONS.NAMESPACE, [IMPLEMENTATIONS.ACTIONS.LIST_IMPLEMENTATIONS]),
    ...mapActions(TOPOLOGIES.NAMESPACE, [TOPOLOGIES.ACTIONS.DATA.GET_TOPOLOGIES]),
    async submit() {
      await this.replaceTopologyData(this.event, this.altName.toString(), this.folderId, this.setDiagramState)
      this.isOpen = false
    },
    onClose() {
      this.altName = ''
      this.folderId = null
      this.setDiagramState = false
    },
  },
  watch: {
    topologyImportState: {
      deep: true,
      handler(state) {
        this.implementationsProject = state.implementationsProject.slice(0)
        this.implementationsFile = state.implementationsFile.map((file) => {
          if (
            this.implementationsProject.filter((implementationProject) => implementationProject.url === file).length
          ) {
            return { name: file, replace: file }
          } else {
            return { name: file, replace: '' }
          }
        })
      },
    },
  },
  async created() {
    await this[IMPLEMENTATIONS.ACTIONS.LIST_IMPLEMENTATIONS]()
    events.listen(EVENTS.MODAL.TOPOLOGY.IMPORT, (event) => {
      this.event = event.e
      if (event.folderId) {
        this.folderId = event.folderId
      }
      if (event.setDiagramState) {
        this.setDiagramState = event.setDiagramState
      }
      this.isOpen = true
    })
  },
}
</script>
