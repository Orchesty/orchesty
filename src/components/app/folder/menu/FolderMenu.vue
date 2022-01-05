<template>
  <v-menu bottom left>
    <template #activator="{ on, attrs }">
      <v-btn icon v-bind="attrs" v-on="on">
        <v-icon>mdi-dots-vertical</v-icon>
      </v-btn>
    </template>

    <v-list dense>
      <v-list-item @click="events.emit(EVENTS.MODAL.FOLDER.CREATE, { topology })">
        <v-list-item-title>{{ $t(`folders.menu.create`) }}</v-list-item-title>
      </v-list-item>
      <v-list-item @click="events.emit(EVENTS.MODAL.TOPOLOGY.CREATE, { topology })">
        <v-list-item-title>{{ $t(`folders.menu.createTopology`) }}</v-list-item-title>
      </v-list-item>
      <v-list-item link @click="$refs.import.click()">
        <input
          id="import"
          ref="import"
          type="file"
          hidden
          @change="
            (e) => {
              fetchDiagram(e)
            }
          "
        />
        <v-list-item-title>{{ $t(`folders.menu.importTopology`) }}</v-list-item-title>
      </v-list-item>
      <v-list-item @click="events.emit(EVENTS.MODAL.FOLDER.EDIT, { topology })">
        <v-list-item-title>{{ $t(`folders.menu.edit`) }}</v-list-item-title>
      </v-list-item>
      <v-list-item v-if="topology.children.length === 0" @click="events.emit(EVENTS.MODAL.FOLDER.DELETE, { topology })">
        <v-list-item-title>{{ $t(`folders.menu.delete`) }}</v-list-item-title>
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
    async fetchDiagram(e) {
      await this.fetchTopologyDiagram(e)
    },
  },
}
</script>
