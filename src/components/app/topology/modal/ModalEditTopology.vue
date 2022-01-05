<template>
  <modal-template
    v-model="isOpen"
    :title="$t('topologies.modals.edit.title')"
    :on-cancel="() => $refs.form.reset()"
    :on-close="() => $refs.form.reset()"
    :on-confirm="() => $refs.form.submit()"
  >
    <template #default>
      <v-col cols="12">
        <topology-form ref="form" :data="data" :sending-btn="false" :on-submit="submit" />
      </v-col>
    </template>
    <template #sendingButton>
      <sending-button
        :sending-title="$t('button.sending.editing')"
        :is-sending="state.isSending"
        :flat="false"
        :button-title="$t('button.edit')"
        :on-click="() => $refs.form.submit()"
        :color="'primary'"
      />
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
import SendingButton from '@/components/commons/button/SendingButton'

export default {
  name: 'ModalEditTopology',
  components: { SendingButton, ModalTemplate, TopologyForm },
  data: () => ({
    isOpen: false,
    data: null,
    topologyId: null,
  }),
  computed: {
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    state() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE]([API.topology.edit.id, API.topology.getList.id])
    },
  },
  methods: {
    ...mapActions(TOPOLOGIES.NAMESPACE, [TOPOLOGIES.ACTIONS.TOPOLOGY.EDIT, TOPOLOGIES.ACTIONS.DATA.GET_TOPOLOGIES]),
    submit(form) {
      this[TOPOLOGIES.ACTIONS.TOPOLOGY.EDIT]({ data: { ...form }, topologyId: this.topologyId }).then(async (res) => {
        if (res) {
          this.isOpen = false
          await this[TOPOLOGIES.ACTIONS.DATA.GET_TOPOLOGIES]()
        }
      })
    },
  },
  created() {
    events.listen(EVENTS.MODAL.TOPOLOGY.EDIT, ({ topology }) => {
      this.isOpen = true
      if (!topology) topology = {}
      this.data = topology
      this.topologyId = topology.id
    })
  },
}
</script>
