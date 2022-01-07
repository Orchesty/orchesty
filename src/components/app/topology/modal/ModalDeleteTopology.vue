<template>
  <modal-template v-model="isOpen" :title="$t('topologies.modals.delete.title')" :on-confirm="() => submit()">
    <template #default>
      <v-col cols="12">
        {{ $t('topologies.modals.delete.body', [topology ? `${topology.name} v.${topology.version}` : '']) }}
        <template v-if="topology && topology.enabled">{{ $t('topologies.modals.delete.enabledWarning') }}</template>
      </v-col>
    </template>
    <template #sendingButton>
      <sending-button
        v-if="topology && topology.enabled"
        :sending-title="$t('button.sending.disabling')"
        :is-sending="state.isSending"
        :button-title="$t('button.disable')"
        :on-click="disable"
        :flat="false"
        class="mr-3"
      />
      <sending-button
        :sending-title="$t('button.sending.deleting')"
        :is-sending="state.isSending"
        :button-title="$t('button.delete')"
        :on-click="submit"
        :flat="false"
        :color="topology && topology.enabled ? 'error' : 'primary'"
      />
    </template>
  </modal-template>
</template>

<script>
import { events, EVENTS } from '../../../../services/utils/events'
import ModalTemplate from '../../../commons/modal/ModalTemplate'
import { mapActions, mapGetters } from 'vuex'
import { TOPOLOGIES } from '../../../../store/modules/topologies/types'
import { REQUESTS_STATE } from '../../../../store/modules/api/types'
import { API } from '../../../../api'
import SendingButton from '@/components/commons/button/SendingButton'
import { ROUTES } from '@/services/enums/routerEnums'

export default {
  name: 'ModalDeleteTopology',
  components: { SendingButton, ModalTemplate },
  data: () => ({
    isOpen: false,
    topology: null,
  }),
  computed: {
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    state() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE]([API.topology.delete.id, API.topology.getList.id])
    },
  },
  methods: {
    ...mapActions(TOPOLOGIES.NAMESPACE, [
      TOPOLOGIES.ACTIONS.TOPOLOGY.DELETE,
      TOPOLOGIES.ACTIONS.DATA.GET_TOPOLOGIES,
      TOPOLOGIES.ACTIONS.TOPOLOGY.DISABLE,
    ]),
    async submit() {
      await this[TOPOLOGIES.ACTIONS.TOPOLOGY.DELETE]({
        topologyId: this.topology.id,
      }).then((res) => {
        if (res) {
          this.isOpen = false
          if (this.topology.id === this.$route.params.id) {
            this.$router.push({ name: ROUTES.DASHBOARD })
          }
        }
      })
    },
    async disable() {
      await this[TOPOLOGIES.ACTIONS.TOPOLOGY.DISABLE]({
        topologyID: this.topology.id,
      }).then((res) => {
        if (res) {
          this.isOpen = false
        }
      })
    },
  },
  created() {
    events.listen(EVENTS.MODAL.TOPOLOGY.DELETE, ({ topology }) => {
      this.topology = topology
      this.isOpen = true
    })
  },
}
</script>

<style></style>
