<template>
  <v-menu offset-y transition="slide-y-transition">
    <template #activator="{ on }">
      <div ref="actionButton" class="mx-auto button-wrapper" v-on="on">
        <app-special-button icon="mdi-plus" />
      </div>
    </template>

    <v-list dense>
      <v-list-item @click="events.emit(EVENTS.MODAL.TOPOLOGY.CREATE, {})">
        <v-list-item-title class="d-flex justify-space-between align-center">
          <span class="mr-2">{{ $t(`contextMenu.topology.create`) }}</span>
          <v-icon color="primary" dense>account_tree</v-icon>
        </v-list-item-title>
      </v-list-item>
      <v-list-item @click="events.emit(EVENTS.MODAL.FOLDER.CREATE, {})">
        <v-list-item-title class="d-flex justify-space-between align-center">
          <span class="mr-2">{{ $t(`contextMenu.folder.create`) }}</span>
          <v-icon color="primary" dense>mdi-folder-plus</v-icon>
        </v-list-item-title>
      </v-list-item>
      <v-list-item link @click="$refs.import.click()">
        <input id="import" ref="import" type="file" hidden @change="fetchDiagram" @click="$event.target.value = null" />
        <v-list-item-title class="d-flex justify-space-between align-center">
          <span class="mr-2">{{ $t(`contextMenu.topology.import`) }}</span>
          <v-icon color="primary" dense>mdi-import</v-icon>
        </v-list-item-title>
      </v-list-item>
    </v-list>
  </v-menu>
</template>

<script>
import { events, EVENTS } from '@/services/utils/events'
import ImportTopologyMixin from '@/components/commons/mixins/ImportTopologyMixin'
import AppSpecialButton from '@/components/commons/button/AppSpecialButton'

export default {
  name: 'TopologyAddHandler',
  mixins: [ImportTopologyMixin],
  components: { AppSpecialButton },
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
