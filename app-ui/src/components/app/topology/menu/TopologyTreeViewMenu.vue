<template>
  <v-menu bottom left>
    <template #activator="{ on, attrs }">
      <v-btn icon v-bind="attrs" v-on="on">
        <app-icon> mdi-dots-vertical </app-icon>
      </v-btn>
    </template>

    <v-list dense>
      <template v-for="(item, index) in topologyTreeViewMenuItems">
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
import AppIcon from '@/components/commons/icon/AppIcon'
import AppListItem from '@/components/commons/AppListItem'
import { redirectTo } from '@/services/utils/utils'

export default {
  name: 'TopologyTreeViewMenu',
  components: { AppListItem, AppIcon },
  data() {
    return {
      TOPOLOGY_ENUMS,
      events,
      EVENTS,
      value: null,
      isOpen: false,
      topologyTreeViewMenuItems: [
        {
          conditional: this.isEnabled(),
          spanClass: 'success--text',
          text: `contextMenu.topology.run`,
          iconColor: 'success',
          icon: 'mdi-play-circle',
          onClick: () => this.events.emit(EVENTS.MODAL.TOPOLOGY.RUN, this.topology),
        },
        {
          text: `contextMenu.topology.edit`,
          icon: 'mdi-pencil',
          onClick: () => this.events.emit(EVENTS.MODAL.TOPOLOGY.EDIT, this.topology),
        },
        {
          spanClass: 'error--text',
          text: `contextMenu.topology.delete`,
          iconColor: 'error',
          icon: 'mdi-delete',
          onClick: () => this.events.emit(EVENTS.MODAL.TOPOLOGY.DELETE, this.topology),
        },
        {
          text: `contextMenu.topology.move`,
          icon: 'mdi-arrow-bottom-right',
          onClick: () =>
            this.events.emit(EVENTS.MODAL.TOPOLOGY.MOVE, { topologies: this.topologies, topology: this.topology }),
        },
        {
          text: `contextMenu.topology.clone`,
          icon: 'mdi-content-copy',
          onClick: this.clone,
        },
        {
          text: `contextMenu.topology.export`,
          icon: 'mdi-export',
          onClick: this.exportXML,
        },
      ],
    }
  },
  methods: {
    ...mapActions(TOPOLOGIES.NAMESPACE, [
      TOPOLOGIES.ACTIONS.TOPOLOGY.CLONE,
      TOPOLOGIES.ACTIONS.DATA.GET_TOPOLOGIES,
      TOPOLOGIES.ACTIONS.TOPOLOGY.RETURN_DIAGRAM,
      TOPOLOGIES.ACTIONS.TOPOLOGY.GET_BY_ID,
    ]),
    async clone() {
      let response = await this[TOPOLOGIES.ACTIONS.TOPOLOGY.CLONE](this.topology._id)
      if (response) {
        await this[TOPOLOGIES.ACTIONS.DATA.GET_TOPOLOGIES]()
        await this[TOPOLOGIES.ACTIONS.TOPOLOGY.GET_BY_ID](response._id)
        await redirectTo(this.$router, { name: ROUTES.TOPOLOGY.VIEWER, params: { id: response._id } })
      }
    },
    async exportXML() {
      let diagram = await this[TOPOLOGIES.ACTIONS.TOPOLOGY.RETURN_DIAGRAM](this.topology._id)
      download(diagram, `${this.topology.name}.v${this.topology.version}` + '.tplg', 'application/bpmn+xml')
    },
    isEnabled() {
      return this.topology.visibility === TOPOLOGY_ENUMS.PUBLIC && this.topology.enabled
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
