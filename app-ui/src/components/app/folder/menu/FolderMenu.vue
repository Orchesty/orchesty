<template>
  <v-menu bottom left>
    <template #activator="{ on, attrs }">
      <v-btn icon v-bind="attrs" v-on="on">
        <v-icon color="primary">mdi-dots-vertical</v-icon>
      </v-btn>
    </template>

    <v-list dense>
      <v-list-item @click="events.emit(EVENTS.MODAL.FOLDER.CREATE, { topology })">
        <v-list-item-title class="d-flex justify-space-between align-center">
          <span class="mr-2">{{ $t(`contextMenu.folder.create`) }}</span>
          <v-icon color="primary" dense>mdi-folder-plus</v-icon>
        </v-list-item-title>
      </v-list-item>
      <v-list-item @click="events.emit(EVENTS.MODAL.TOPOLOGY.CREATE, { topology })">
        <v-list-item-title class="d-flex justify-space-between align-center">
          <span class="mr-2">{{ $t(`contextMenu.folder.createTopology`) }}</span>
          <v-icon color="primary" dense>account_tree</v-icon>
        </v-list-item-title>
      </v-list-item>
      <v-list-item link @click="$refs.import.click()">
        <input
          id="import"
          ref="import"
          type="file"
          hidden
          @change="
            (e) => {
              fetchDiagram(e, topology)
            }
          "
        />
        <v-list-item-title class="d-flex justify-space-between align-center">
          <span class="mr-2">{{ $t(`contextMenu.folder.importTopology`) }}</span>
          <v-icon color="primary" dense>mdi-import</v-icon>
        </v-list-item-title>
      </v-list-item>
      <v-list-item @click="events.emit(EVENTS.MODAL.FOLDER.EDIT, { topology })">
        <v-list-item-title class="d-flex justify-space-between align-center">
          <span class="mr-2">{{ $t(`contextMenu.folder.edit`) }}</span>
          <v-icon color="primary" dense>edit</v-icon>
        </v-list-item-title>
      </v-list-item>
      <v-list-item v-if="topology.children.length === 0" @click="events.emit(EVENTS.MODAL.FOLDER.DELETE, { topology })">
        <v-list-item-title class="d-flex justify-space-between align-center">
          <span class="mr-2 error--text">{{ $t(`contextMenu.folder.delete`) }}</span>
          <v-icon color="error" dense>mdi-delete</v-icon>
        </v-list-item-title>
      </v-list-item>
    </v-list>
  </v-menu>
</template>

<script>
import { events, EVENTS } from '../../../../services/utils/events'
import ImportTopologyMixin from '@/components/commons/mixins/ImportTopologyMixin'
export default {
  name: 'FolderMenu',
  mixins: [ImportTopologyMixin],
  data() {
    return {
      events,
      EVENTS,
      value: null,
      isOpen: false,
    }
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
  methods: {
    async fetchDiagram(e, folder) {
      await this.fetchTopologyDiagram(e, folder.id)
    },
  },
}
</script>
