<template>
  <v-menu bottom left>
    <template #activator="{ on, attrs }">
      <app-button icon :on="on" :attrs="attrs">
        <template #icon>
          <app-icon> mdi-dots-vertical </app-icon>
        </template>
      </app-button>
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
import { mapActions, mapGetters } from 'vuex'
import { TOPOLOGIES } from '@/store/modules/topologies/types'
import download from '@/services/utils/download'
import { REQUESTS_STATE } from '@/store/modules/api/types'
import { API } from '@/api'
import AppIcon from '@/components/commons/icon/AppIcon'
import AppButton from '@/components/commons/button/AppButton'
import AppListItem from '@/components/commons/AppListItem'
import { ROUTES } from '@/services/enums/routerEnums'
import { redirectTo } from '@/services/utils/utils'

export default {
  name: 'TopologyDetailMenu',
  components: { AppListItem, AppButton, AppIcon },
  data() {
    return {
      events,
      EVENTS,
      isOpen: false,
      topologyTreeViewMenuItems: [
        {
          text: `topologies.menu.edit`,
          icon: 'mdi-pencil',
          onClick: () => this.events.emit(EVENTS.MODAL.TOPOLOGY.EDIT, this.topologyActive),
        },
        {
          text: `topologies.menu.delete`,
          icon: 'mdi-delete',
          iconColor: 'error',
          spanClass: 'error--text',
          onClick: () => this.events.emit(EVENTS.MODAL.TOPOLOGY.DELETE, this.topologyActive),
        },
        {
          text: `topologies.menu.move`,
          icon: 'mdi-arrow-bottom-right',
          onClick: () =>
            this.events.emit(EVENTS.MODAL.TOPOLOGY.MOVE, {
              topologies: this.topologyAll,
              topology: this.topologyActive,
            }),
        },
        {
          text: `topologies.menu.clone`,
          icon: 'mdi-content-copy',
          onClick: this.clone,
        },
        {
          text: `topologies.menu.export`,
          icon: 'mdi-export',
          onClick: this.exportXML,
        },
      ],
    }
  },
  computed: {
    ...mapGetters(TOPOLOGIES.NAMESPACE, {
      topologyActive: TOPOLOGIES.GETTERS.GET_ACTIVE_TOPOLOGY,
      topologyAll: TOPOLOGIES.GETTERS.GET_ALL_TOPOLOGIES,
      topologyActiveDiagram: TOPOLOGIES.GETTERS.GET_ACTIVE_TOPOLOGY_DIAGRAM,
    }),
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    cloneState() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE](API.topology.clone.id).isSending
    },
  },
  methods: {
    ...mapActions(TOPOLOGIES.NAMESPACE, [
      TOPOLOGIES.ACTIONS.TOPOLOGY.CLONE,
      TOPOLOGIES.ACTIONS.TOPOLOGY.GET_DIAGRAM,
      TOPOLOGIES.ACTIONS.TOPOLOGY.GET_BY_ID,
      TOPOLOGIES.ACTIONS.DATA.GET_TOPOLOGIES,
    ]),
    async clone() {
      let response = await this[TOPOLOGIES.ACTIONS.TOPOLOGY.CLONE](this.topologyActive._id)
      if (response) {
        await this[TOPOLOGIES.ACTIONS.DATA.GET_TOPOLOGIES]()
        await this[TOPOLOGIES.ACTIONS.TOPOLOGY.GET_BY_ID](response._id)
        await redirectTo(this.$router, { name: ROUTES.TOPOLOGY.VIEWER, params: { id: response._id } })
      }
    },
    async exportXML() {
      await this[TOPOLOGIES.ACTIONS.TOPOLOGY.GET_DIAGRAM](this.topologyActive._id)
      download(
        this.topologyActiveDiagram,
        `${this.topologyActive.name}.v${this.topologyActive.version}` + '.tplg',
        'application/bpmn+xml'
      )
    },
  },
}
</script>
