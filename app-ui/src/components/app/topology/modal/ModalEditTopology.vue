<template>
  <modal-template
    v-model="isOpen"
    :title="$t('topologies.modals.edit.title', { msg: callbackData ? callbackData.name : '' })"
    :on-close="() => onClose"
    :on-confirm="() => $refs.form.submit()"
  >
    <template #default>
      <v-row dense>
        <v-col cols="12">
          <topology-form ref="form" :callback-data="callbackData" :sending-btn="false" :on-submit="submit" />
        </v-col>
      </v-row>
    </template>
    <template #sendingButton>
      <v-row dense>
        <v-col cols="12" class="d-flex justify-end">
          <app-button
            :sending-title="$t('button.sending.editing')"
            :is-sending="state.isSending"
            :flat="false"
            :button-title="$t('button.edit')"
            :on-click="() => $refs.form.submit()"
            :color="'primary'"
          />
        </v-col>
      </v-row>
    </template>
  </modal-template>
</template>

<script>
import { events, EVENTS } from '../../../../services/utils/events'
import ModalTemplate from '../../../commons/modal/ModalTemplate'
import { TOPOLOGIES } from '../../../../store/modules/topologies/types'
import { mapActions, mapGetters } from 'vuex'
import { REQUESTS_STATE } from '../../../../store/modules/api/types'
import { API } from '../../../../api'
import TopologyForm from '../form/TopologyForm'
import AppButton from '@/components/commons/button/AppButton'

export default {
  name: 'ModalEditTopology',
  components: { AppButton, ModalTemplate, TopologyForm },
  data: () => ({
    isOpen: false,
    callbackData: null,
    topologyId: null,
  }),
  computed: {
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    state() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE]([API.topology.edit.id, API.topology.getList.id])
    },
  },
  methods: {
    ...mapActions(TOPOLOGIES.NAMESPACE, [
      TOPOLOGIES.ACTIONS.TOPOLOGY.EDIT,
      TOPOLOGIES.ACTIONS.DATA.GET_TOPOLOGIES,
      TOPOLOGIES.ACTIONS.TOPOLOGY.GET_BY_ID,
    ]),
    submit(form) {
      this[TOPOLOGIES.ACTIONS.TOPOLOGY.EDIT]({ data: { ...form }, id: this.topologyId }).then(async (res) => {
        if (res) {
          await this[TOPOLOGIES.ACTIONS.DATA.GET_TOPOLOGIES]()
          await this[TOPOLOGIES.ACTIONS.TOPOLOGY.GET_BY_ID](this.topologyId)
          this.isOpen = false
        }
      })
    },
    onClose() {
      this.callbackData = null
      this.folderId = null
    },
  },
  created() {
    events.listen(EVENTS.MODAL.TOPOLOGY.EDIT, (topology) => {
      this.isOpen = true
      if (!topology) topology = {}
      this.callbackData = topology
      this.topologyId = topology._id
    })
  },
}
</script>
