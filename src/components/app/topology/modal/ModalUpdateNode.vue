<template>
  <modal-template
    v-if="topologY"
    v-model="isOpen"
    :title="`${topologY.enabled ? 'Disable' : 'Enable'} node`"
    :on-confirm="() => submit()"
  >
    <template #default>
      <v-row dense>
        <v-col cols="12"> {{ topologY.enabled ? 'Disable' : 'Enable' }} starting point? </v-col>
      </v-row>
    </template>
    <template #sendingButton>
      <v-row dense>
        <v-col cols="12" class="d-flex justify-end">
          <app-button :button-title="topologY.enabled ? 'Disable' : 'Enable'" :on-click="submit" />
        </v-col>
      </v-row>
    </template>
  </modal-template>
</template>

<script>
import { events, EVENTS } from '../../../../services/utils/events'
import ModalTemplate from '../../../commons/modal/ModalTemplate'
import { mapActions, mapGetters, mapState } from 'vuex'
import { TOPOLOGIES } from '../../../../store/modules/topologies/types'
import { REQUESTS_STATE } from '../../../../store/modules/api/types'
import { API } from '../../../../api'
import AppButton from '@/components/commons/button/AppButton'

export default {
  name: 'ModalUpdateNode',
  components: { AppButton, ModalTemplate },
  data: () => ({
    isOpen: false,
    topologY: null,
  }),
  computed: {
    ...mapState(TOPOLOGIES.NAMESPACE, ['topology']),
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    state() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE]([API.topology.delete.id, API.topology.getList.id])
    },
  },
  methods: {
    ...mapActions(TOPOLOGIES.NAMESPACE, [TOPOLOGIES.ACTIONS.NODE.UPDATE, TOPOLOGIES.ACTIONS.TOPOLOGY.GET_DIAGRAM]),
    async submit() {
      console.log(this.topologY.enabled)
      await this[TOPOLOGIES.ACTIONS.NODE.UPDATE]({
        nodeId: this.topologY._id,
        enabled: !this.topologY.enabled,
        topologyId: this.topology._id,
      }).then((res) => {
        if (res) {
          this.isOpen = false
          this.topologY = null
        }
      })
    },
  },
  created() {
    events.listen(EVENTS.MODAL.NODE.UPDATE, (e) => {
      this.topologY = e
      this.isOpen = true
    })
  },
}
</script>

<style></style>
