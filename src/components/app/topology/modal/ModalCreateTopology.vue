<template>
  <modal-template
    v-model="isOpen"
    :title="$t('topologies.modals.create.title')"
    :on-close="() => $refs.form.reset()"
    :on-cancel="() => $refs.form.reset()"
    :on-confirm="() => $refs.form.submit()"
  >
    <template #default>
      <v-col cols="12">
        <topology-form ref="form" :data="data" :on-submit="submit" />
      </v-col>
    </template>
    <template #sendingButton>
      <sending-button
        :sending-title="$t('button.sending.creating')"
        :is-sending="state.isSending"
        :flat="false"
        :button-title="$t('button.create')"
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
import SendingButton from '@/components/commons/button/AppButton'
import { ROUTES } from '@/services/enums/routerEnums'

export default {
  name: 'ModalCreateTopology',
  components: { SendingButton, ModalTemplate, TopologyForm },
  data: () => ({
    isOpen: false,
    data: null,
  }),
  computed: {
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    state() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE]([API.topology.create.id, API.topology.getList.id])
    },
  },
  methods: {
    ...mapActions(TOPOLOGIES.NAMESPACE, [
      TOPOLOGIES.ACTIONS.TOPOLOGY.CREATE,
      TOPOLOGIES.ACTIONS.DATA.GET_TOPOLOGIES,
      TOPOLOGIES.ACTIONS.TOPOLOGY.SAVE_DIAGRAM,
      TOPOLOGIES.ACTIONS.TOPOLOGY.GET_BY_ID,
    ]),
    async submit(form) {
      await this[TOPOLOGIES.ACTIONS.TOPOLOGY.CREATE](form).then(async (res) => {
        if (res) {
          this.isOpen = false
          await this[TOPOLOGIES.ACTIONS.DATA.GET_TOPOLOGIES]()
          await this[TOPOLOGIES.ACTIONS.TOPOLOGY.GET_BY_ID]({ id: res._id })
          await this.$router.push({ name: ROUTES.TOPOLOGY.VIEWER, params: { id: res._id, isNewTopology: true } })
        }
      })
    },
  },
  created() {
    events.listen(EVENTS.MODAL.TOPOLOGY.CREATE, ({ topology }) => {
      this.isOpen = true
      if (!topology) topology = {}
      if (topology.type === 'CATEGORY') topology = { category: topology.id }
      this.data = topology
    })
  },
}
</script>
