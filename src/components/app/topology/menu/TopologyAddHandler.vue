<template>
  <v-menu offset-y transition="slide-y-transition">
    <template #activator="{ on }">
      <div ref="actionButton" class="mx-auto button-wrapper" v-on="on">
        <green-button icon="mdi-plus" />
      </div>
    </template>

    <v-list dense>
      <v-list-item @click="events.emit(EVENTS.MODAL.TOPOLOGY.CREATE, {})">
        <v-list-item-title>{{ $t(`topologies.menu.create`) }}</v-list-item-title>
      </v-list-item>
      <v-list-item @click="events.emit(EVENTS.MODAL.FOLDER.CREATE, {})">
        <v-list-item-title>{{ $t(`folders.menu.create`) }}</v-list-item-title>
      </v-list-item>
      <v-list-item link @click="$refs.import.click()">
        <input id="import" ref="import" type="file" hidden @change="fetchDiagram" @click="$event.target.value = null" />
        <v-list-item-title>{{ $t(`topologies.menu.import`) }}</v-list-item-title>
      </v-list-item>
    </v-list>
  </v-menu>
</template>

<script>
import GreenButton from '../../../commons/button/GreenButton'
import { events, EVENTS } from '../../../../events'
import ImportTopologyMixin from '@/components/commons/mixins/ImportTopologyMixin'

export default {
  name: 'TopologyAddHandler',
  mixins: [ImportTopologyMixin],
  components: { GreenButton },
  data() {
    return {
      events,
      EVENTS,
      value: null,
      isOpen: false,
    }
  },
  methods: {
    async fetchDiagram(e) {
      await this.fetchTopologyDiagram(e)
    },
  },
}
</script>
