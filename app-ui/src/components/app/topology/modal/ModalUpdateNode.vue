<template>
  <modal-template
    v-if="node"
    v-model="isOpen"
    :title="`${node.enabled ? 'Disable' : 'Enable'} node`"
    :on-confirm="() => submit()"
  >
    <template #default>
      <v-row dense>
        <v-col cols="12"> {{ node.enabled ? 'Disable' : 'Enable' }} starting point? </v-col>
      </v-row>
    </template>
    <template #sendingButton>
      <v-row dense>
        <v-col cols="12" class="d-flex justify-end">
          <app-button :button-title="node.enabled ? 'Disable' : 'Enable'" :on-click="submit" />
        </v-col>
      </v-row>
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
import AppButton from '@/components/commons/button/AppButton'

export default {
  name: 'ModalUpdateNode',
  components: { AppButton, ModalTemplate },
  data: () => ({
    isOpen: false,
    node: null,
  }),
  computed: {
    ...mapGetters(TOPOLOGIES.NAMESPACE, { topologyActive: TOPOLOGIES.GETTERS.GET_ACTIVE_TOPOLOGY }),
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    state() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE]([API.topology.delete.id, API.topology.getList.id])
    },
  },
  methods: {
    ...mapActions(TOPOLOGIES.NAMESPACE, [
      TOPOLOGIES.ACTIONS.NODE.UPDATE,
      TOPOLOGIES.ACTIONS.TOPOLOGY.GET_DIAGRAM,
      TOPOLOGIES.ACTIONS.TOPOLOGY.GET_BY_ID,
    ]),
    async submit() {
      await this[TOPOLOGIES.ACTIONS.NODE.UPDATE]({
        nodeId: this.node._id,
        enabled: !this.node.enabled,
        topologyId: this.topologyActive._id,
      }).then((res) => {
        if (res) {
          this[TOPOLOGIES.ACTIONS.TOPOLOGY.GET_BY_ID](this.topologyActive._id)
          this.isOpen = false
          this.node = null
        }
      })
    },
  },
  created() {
    events.listen(EVENTS.MODAL.NODE.UPDATE, (node) => {
      this.node = node
      this.isOpen = true
    })
  },
}
</script>

<style></style>
