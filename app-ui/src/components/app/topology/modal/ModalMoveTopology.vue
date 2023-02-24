<template>
  <modal-template v-model="isOpen" :title="$t('modal.header.moveTopology')">
    <template #default>
      <v-row dense>
        <v-col cols="12">
          <topology-move-tree-view
            v-model="selectedCategoryId"
            :topologies="topologies"
            :topology="topology"
          />
        </v-col>
      </v-row>
    </template>
    <template #sendingButton>
      <v-row dense>
        <v-col cols="12" class="d-flex justify-end">
          <app-button
            :sending-title="$t('button.sending.moving')"
            :is-sending="state.isSending"
            :button-title="$t('button.move')"
            :on-click="submit"
            :flat="false"
            class="mt-1"
          />
        </v-col>
      </v-row>
    </template>
  </modal-template>
</template>

<script>
import { events, EVENTS } from "../../../../services/utils/events"
import ModalTemplate from "../../../commons/modal/ModalTemplate"
import { TOPOLOGIES } from "@/store/modules/topologies/types"
import { mapActions, mapGetters } from "vuex"
import { REQUESTS_STATE } from "@/store/modules/api/types"
import { API } from "@/api"
import TopologyMoveTreeView from "../treeview/TopologyMoveTreeView"
import AppButton from "@/components/commons/button/AppButton"

export default {
  name: "ModalMoveTopology",
  components: { AppButton, ModalTemplate, TopologyMoveTreeView },
  data: () => ({
    isOpen: false,
    topologies: [],
    topology: {},
    selectedCategoryId: null,
  }),
  computed: {
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    state() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE](API.topology.move.id)
    },
  },
  methods: {
    ...mapActions(TOPOLOGIES.NAMESPACE, [
      TOPOLOGIES.ACTIONS.TOPOLOGY.MOVE,
      TOPOLOGIES.ACTIONS.DATA.GET_TOPOLOGIES,
    ]),
    submit() {
      this[TOPOLOGIES.ACTIONS.TOPOLOGY.MOVE]({
        categoryId: this.selectedCategoryId,
        topologyId: this.topology._id,
        name: this.topology.name,
      }).then(async (res) => {
        if (res) {
          await this[TOPOLOGIES.ACTIONS.DATA.GET_TOPOLOGIES]()
          this.isOpen = false
        }
      })
    },
  },
  created() {
    events.listen(EVENTS.MODAL.TOPOLOGY.MOVE, ({ topologies, topology }) => {
      this.topologies = JSON.parse(JSON.stringify(topologies))
      this.topology = topology
      this.isOpen = true
    })
  },
}
</script>
