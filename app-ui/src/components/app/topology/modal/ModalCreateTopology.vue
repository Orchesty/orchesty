<template>
  <modal-template
    v-model="isOpen"
    :title="$t('topologies.modals.create.title')"
    :on-close="onClose"
    :on-confirm="() => $refs.form.submit()"
  >
    <template #default>
      <v-row dense>
        <v-col cols="12">
          <topology-form ref="form" :callback-data="callbackData" :on-submit="submit" />
        </v-col>
      </v-row>
    </template>
    <template #sendingButton>
      <v-row dense>
        <v-col cols="12" class="d-flex justify-end">
          <app-button
            :sending-title="$t('button.sending.creating')"
            :is-sending="state.isSending"
            :flat="false"
            :button-title="$t('button.create')"
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
import { ROUTES } from '@/services/enums/routerEnums'
import { redirectTo } from '@/services/utils/utils'

export default {
  name: 'ModalCreateTopology',
  components: { AppButton, ModalTemplate, TopologyForm },
  data: () => ({
    isOpen: false,
    callbackData: null,
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
      TOPOLOGIES.ACTIONS.TOPOLOGY.GET_BY_ID,
    ]),
    async submit(form) {
      await this[TOPOLOGIES.ACTIONS.TOPOLOGY.CREATE](form).then(async (response) => {
        if (response) {
          await this[TOPOLOGIES.ACTIONS.DATA.GET_TOPOLOGIES]()
          await this[TOPOLOGIES.ACTIONS.TOPOLOGY.GET_BY_ID](response._id)
          await redirectTo(this.$router, { name: ROUTES.TOPOLOGY.VIEWER, params: { id: response._id } })
          this.isOpen = false
        }
      })
    },
    onClose() {
      this.callbackData = null
    },
  },
  created() {
    events.listen(EVENTS.MODAL.TOPOLOGY.CREATE, ({ topology }) => {
      this.isOpen = true
      if (!topology) topology = {}
      if (topology.type === 'CATEGORY') topology = { folder: topology.id }
      this.callbackData = topology
    })
  },
}
</script>
