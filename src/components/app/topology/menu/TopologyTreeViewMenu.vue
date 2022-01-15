<template>
  <v-menu bottom left>
    <template #activator="{ on, attrs }">
      <v-btn icon v-bind="attrs" v-on="on" @click="updateTopology">
        <v-icon>mdi-dots-vertical</v-icon>
      </v-btn>
    </template>

    <v-list dense>
      <v-list-item @click="events.emit(EVENTS.MODAL.TOPOLOGY.EDIT, { topology })">
        <v-list-item-title> {{ $t(`topologies.menu.edit`) }}</v-list-item-title>
      </v-list-item>
      <v-list-item @click="events.emit(EVENTS.MODAL.TOPOLOGY.MOVE, { topologies, topology })">
        <v-list-item-title>{{ $t(`topologies.menu.move`) }}</v-list-item-title>
      </v-list-item>
      <v-list-item @click="events.emit(EVENTS.MODAL.TOPOLOGY.DELETE, { topology })">
        <v-list-item-title>{{ $t(`topologies.menu.delete`) }}</v-list-item-title>
      </v-list-item>
      <v-list-item
        v-if="topology.visibility === TOPOLOGY_ENUMS.PUBLIC && topology.enabled"
        @click="events.emit(EVENTS.MODAL.TOPOLOGY.RUN, { topology })"
      >
        <v-list-item-title>{{ $t(`topologies.menu.run`) }}</v-list-item-title>
      </v-list-item>
      <v-list-item @click="clone">
        <v-list-item-title>{{ $t(`topologies.menu.clone`) }}</v-list-item-title>
      </v-list-item>
      <v-list-item @click="exportXML">
        <v-list-item-title>{{ $t(`topologies.menu.export`) }}</v-list-item-title>
      </v-list-item>
    </v-list>
  </v-menu>
</template>

<script>
import { events, EVENTS } from '../../../../services/utils/events'
import { mapActions } from 'vuex'
import { TOPOLOGIES } from '@/store/modules/topologies/types'
import { ROUTES } from '@/services/enums/routerEnums'
import download from '@/services/utils/download'
import { TOPOLOGY_ENUMS } from '@/services/enums/topologyEnums'

export default {
  name: 'TopologyTreeViewMenu',
  components: {},
  data() {
    return {
      TOPOLOGY_ENUMS,
      events,
      EVENTS,
      value: null,
      isOpen: false,
    }
  },
  methods: {
    ...mapActions(TOPOLOGIES.NAMESPACE, [
      TOPOLOGIES.ACTIONS.TOPOLOGY.GET_ID,
      TOPOLOGIES.ACTIONS.TOPOLOGY.CLONE,
      TOPOLOGIES.ACTIONS.TOPOLOGY.GET_DIAGRAM,
    ]),
    async updateTopology() {
      this.value = await this[TOPOLOGIES.ACTIONS.TOPOLOGY.GET_ID](this.topology.id)
    },
    async clone() {
      const response = await this[TOPOLOGIES.ACTIONS.TOPOLOGY.CLONE](this.topology.id)
      await this.$router.push({ name: ROUTES.TOPOLOGY.VIEWER, params: { id: response._id } })
    },
    async exportXML() {
      let diagram = await this[TOPOLOGIES.ACTIONS.TOPOLOGY.GET_DIAGRAM]({ topologyID: this.topology.id })
      download(diagram, `${this.topology.name}.v${this.topology.version}` + '.tplg', 'application/bpmn+xml')
    },
  },
  props: {
    topologies: {
      type: Array,
      required: true,
    },
    topology: {
      type: Object,
      required: true,
    },
  },
}
</script>
