<template>
  <ModalTemplate
    v-model="isOpen"
    :title="$t(`modal.header.changeTopology`)"
    :on-confirm="confirm"
    :disable-enter-confirm="false"
  >
    <template #default>
      <v-row dense>
        <v-col cols="12">
          <AppSelect
            v-model="selectedTopology"
            :label="$t(`form.topology`)"
            :items="topologyOptions"
          />
        </v-col>

        <v-col cols="12">
          <AppSelect
            v-model="selectedNode"
            :label="$t(`form.topologyNode`)"
            :items="topologyNodeOptions"
            :disabled="!selectedTopology"
          />
        </v-col>
      </v-row>
    </template>
    <template #sendingButton>
      <v-row dense>
        <v-col cols="12" class="d-flex justify-end">
          <app-button
            :sending-title="$t(`button.sending.accept`)"
            :is-sending="state.isSending"
            :button-title="$t(`button.accept`)"
            :on-click="confirm"
            :disabled="disabled"
          />
        </v-col>
      </v-row>
    </template>
    <template #button>
      <AppButton
        :sending-title="$t(`button.sending.accept`)"
        :is-sending="state.isSending"
        :button-title="$t(`button.accept`)"
        :on-click="() => (isOpen = !isOpen)"
        color="primary"
        custom-class="mr-2"
      />
    </template>
  </ModalTemplate>
</template>

<script>
import { REQUESTS_STATE } from "@/store/modules/api/types"
import { API } from "@/api"
import AppButton from "@/components/commons/button/AppButton.vue"
import AppSelect from "@/components/commons/AppSelect.vue"
import ModalTemplate from "@/components/commons/modal/ModalTemplate.vue"
import { mapActions, mapGetters } from "vuex"
import { TOPOLOGIES } from "@/store/modules/topologies/types"
import { TOPOLOGY_ENUMS } from "@/services/enums/topologyEnums"
import { getTopologyName } from "@/services/utils/topology"

export default {
  name: "TrashItemChangeTopologyModal",
  components: { AppButton, AppSelect, ModalTemplate },
  data() {
    return {
      isOpen: false,
      topologyNodes: [],
      selectedTopology: null,
      selectedNode: null,
    }
  },
  computed: {
    ...mapGetters(TOPOLOGIES.NAMESPACE, {
      topologies: TOPOLOGIES.GETTERS.GET_ALL_TOPOLOGIES,
    }),
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    disabled() {
      return !this.selectedTopology || !this.selectedNode
    },
    state() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE](
        API.userTask.accept.id,
        API.topology.getTopologyNodes.id
      )
    },
    topologyOptions() {
      return this.topologies
        .filter((topology) => topology.type === TOPOLOGY_ENUMS.TOPOLOGY)
        .map((topology) => {
          return {
            value: topology.id,
            key: getTopologyName(topology, true),
          }
        })
    },
    topologyNodeOptions() {
      return this.topologyNodes.map((node) => {
        return {
          value: node._id,
          key: node.name,
        }
      })
    },
  },
  props: {
    onSubmit: {
      type: Function,
      required: true,
    },
  },
  methods: {
    ...mapActions(TOPOLOGIES.NAMESPACE, [
      TOPOLOGIES.ACTIONS.TOPOLOGY.RETURN_NODES,
    ]),
    confirm() {
      this.onSubmit({
        nodeId: this.selectedNode,
        topologyId: this.selectedTopology,
      }).then((res) => {
        if (res) {
          // this.$emit("reset")
          this.isOpen = false
        }
      })
    },
    async fetchTopologyNodes(topologyId) {
      const response = await this[TOPOLOGIES.ACTIONS.TOPOLOGY.RETURN_NODES](
        topologyId
      )
      if (response) {
        this.topologyNodes = response.items
      }
    },
  },
  watch: {
    selectedTopology(newValue) {
      if (newValue) this.fetchTopologyNodes(newValue)
      else this.selectedNode = null
    },
  },
}
</script>

<style scoped></style>
