<template>
  <v-menu bottom left>
    <template #activator="{ on, attrs }">
      <app-button icon :attrs="attrs" :on="on">
        <template #icon>
          <app-icon>mdi-dots-vertical</app-icon>
        </template>
      </app-button>
    </template>

    <v-list dense>
      <template v-for="(item, index) in folderMenuItems">
        <app-list-item
          :key="index"
          :icon="item.icon"
          :icon-color="item.iconColor || 'primary'"
          :text="item.text"
          :span-class="item.spanClass || ''"
          :conditional="item.conditional"
          :on-click="item.onClick"
        />
      </template>

      <v-list-item link @click="$refs.import.click()">
        <input
          id="import"
          ref="import"
          type="file"
          hidden
          @change="
            (e) => {
              fetchDiagram(e, folder)
            }
          "
        />
        <v-list-item-title class="d-flex justify-space-between align-center">
          <span class="mr-2">{{ $t(`contextMenu.folder.importTopology`) }}</span>
          <app-icon dense>mdi-import</app-icon>
        </v-list-item-title>
      </v-list-item>
    </v-list>
  </v-menu>
</template>

<script>
import { events, EVENTS } from '../../../../services/utils/events'
import ImportTopologyMixin from '@/services/mixins/ImportTopologyMixin'
import AppListItem from '@/components/commons/AppListItem'
import AppButton from '@/components/commons/button/AppButton'
import AppIcon from '@/components/commons/icon/AppIcon'
export default {
  name: 'FolderMenu',
  components: { AppIcon, AppButton, AppListItem },
  mixins: [ImportTopologyMixin],
  data() {
    return {
      events,
      EVENTS,
      value: null,
      isOpen: false,
      folderMenuItems: [],
    }
  },
  props: {
    topologies: {
      type: Array,
      required: true,
    },
    folder: {
      type: Object,
      required: true,
    },
  },
  methods: {
    async fetchDiagram(e, folder) {
      await this.fetchTopologyDiagram(e, folder.id, false)
    },
    isEmpty() {
      return this.folder.children.length === 0
    },
  },
  watch: {
    folder: {
      deep: true,
      immediate: true,
      handler() {
        this.folderMenuItems = [
          {
            text: `contextMenu.folder.create`,
            icon: 'mdi-folder-plus',
            onClick: () => this.events.emit(EVENTS.MODAL.FOLDER.CREATE, { topology: this.folder }),
          },
          {
            text: `contextMenu.folder.createTopology`,
            icon: 'account_tree',
            onClick: () => this.events.emit(EVENTS.MODAL.TOPOLOGY.CREATE, { topology: this.folder }),
          },
          {
            text: `contextMenu.folder.edit`,
            icon: 'edit',
            onClick: () => this.events.emit(EVENTS.MODAL.FOLDER.EDIT, { topology: this.folder }),
          },
          {
            conditional: this.isEmpty(),
            spanClass: 'error--text',
            text: `contextMenu.folder.delete`,
            iconColor: 'error',
            icon: 'mdi-delete',
            onClick: () => this.events.emit(EVENTS.MODAL.FOLDER.DELETE, { topology: this.folder }),
          },
        ]
      },
    },
  },
}
</script>
